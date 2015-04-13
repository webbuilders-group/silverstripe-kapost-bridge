<?php
class KapostService extends Controller implements PermissionProvider {
    /**
     * Authenticator to be used for authenticating the Kapost account
     * @config KapostService.authenticator_class
     * @default MemberAuthenticator
     */
    private static $authenticator_class='MemberAuthenticator';
    
    /**
     * Authenticator to be used for authenticating the Kapost account
     * @config KapostService.authenticator_username_field
     * @default Email
     */
    private static $authenticator_username_field='Email';
    
    /**
     * Authenticator to be used for authenticating the Kapost account
     * @config KapostService.kapost_media_folder
     * @default kapost-media
     */
    private static $kapost_media_folder='kapost-media';
    
    private $exposed_methods=array(
                                    'blogger.getUsersBlogs',
                                    'metaWeblog.newPost',
                                    'metaWeblog.editPost',
                                    'metaWeblog.getPost',
                                    'metaWeblog.getCategories',
                                    'metaWeblog.newMediaObject'
                                    //'metaWeblog.getPreview' //Not implemented
                                );
    
    /**
     * Handles incoming requests to the kapost service
     */
    public function index() {
        $methods=array_fill_keys($this->exposed_methods, array('function'=>array($this, 'handleRPCMethod')));
        
        //Disable Content Negotiator and send the text/xml header (which kapost expects)
        ContentNegotiator::config()->enabled=false;
        $this->response->addHeader('Content-Type', 'text/xml');
        
        $server=new xmlrpc_server($methods, false);
        $server->compress_response=true;
        
        if(Director::isDev()) {
            $server->setDebug(3);
        }
        
        $server->service();
    }
    
    /**
     * Handles RPC request methods
     * @param {xmlrpcmsg} $request XML-RPC Request Object
     */
    public function handleRPCMethod(xmlrpcmsg $request) {
        $username=$request->getParam(1)->getval();
        $password=$request->getParam(2)->getval();
        
        if($this->authenticate($username, $password)) {
            $method=str_replace(array('blogger.', 'metaWeblog.'), '', $request->methodname);
            
            if((!in_array('blogger.'.$method, $this->exposed_methods) && !in_array('metaWeblog.'.$method, $this->exposed_methods)) || !method_exists($this, $method)) {
                return $this->httpError(403, _t('KapostService.METHOD_NOT_ALLOWED', '_Action "{method}" is not allowed on class Kapost Service.', array('method'=>$method)));
            }
            
            
            //Pack params into call to method if they are not the authentication parameters
            $params=array();
            for($i=0;$i<$request->getNumParams();$i++) {
                if($i!=1 && $i!=2) {
                    $params[]=php_xmlrpc_decode($request->getParam($i));
                }
            }
            
            
            //Convert the custom fields to an associtive array
            if(array_key_exists(1, $params) && is_array($params[1]) && array_key_exists('custom_fields', $params[1])) {
                $params[1]['custom_fields']=$this->struct_to_assoc($params[1]['custom_fields']);
            }
            
            
            //Call the method
            $response=call_user_func_array(array($this, $method), $params);
            if($response instanceof xmlrpcresp) {
                return $response; //Response is already encoded so return
            }
            
            //Encode the response
            $response=php_xmlrpc_encode($response);
            if(is_object($response) && $response instanceof xmlrpcval) {
                return new xmlrpcresp($response);
            }
            
            return $this->httpError(500, _t('KapostService.INVALID_RESPONSE', '_Invalid response returned from {method}, response was: {response}', array(
                                                                                                                                                        'method'=>$method,
                                                                                                                                                        'response'=>print_r($response, true)
                                                                                                                                                    )));
        }
        
        
        return $this->httpError(401, _t('KapostService.AUTH_FAILED', '_Authentication Failed, please check the App Center credentials for the SilverStripe end point.'));
    }
    
    /**
     * Checks the authentication of the api request
     * @param {string} $username Username to look up
     * @param {string} $password Password to match against
     * @return {bool} Returns boolean true if authentication passes false otherwise
     */
    protected function authenticate($username, $password) {
        $authenticator=$this->config()->authenticator_class;
        
        $member=$authenticator::authenticate(array(
                                                $this->config()->authenticator_username_field=>$username,
                                                'Password'=>$password
                                            ));
        
        return (!empty($member) && $member!==false && $member->exists()==true && Permission::check('KAPOST_API_ACCESS', 'any', $member));
    }
    
    /**
     * Converts an error to an xmlrpc response
     * @param {int} $errorCode Error code number for the error
     * @param {string} $errorMessage Error message string
     * @return {xmlrpcresp} XML-RPC response object
     */
    public function httpError($errorCode, $errorMessage=null) {
        return new xmlrpcresp(0, $errorCode+10000, $errorMessage);
    }
    
    /**
     * Gets the site config or subsites for the current site
     * @return {array} Nested array of sites
     */
    protected function getUsersBlogs($app_id) {
        if(SiteConfig::has_extension('SiteConfigSubsites')) {
            $response=array();
            
            //Disable subsite filter
            Subsite::disable_subsite_filter();
            
            $subsites=Subsite::get();
            foreach($subsites as $subsite) {
                $response[]=array(
                                'blogid'=>$subsite->ID,
                                'blogname'=>$subsite->Title
                            );
            }
            
            //Re-enable subsite filter
            Subsite::disable_subsite_filter(false);
            
            return $response;
        }
        
        
        $siteConfig=SiteConfig::current_site_config();
        return array(
                    array(
                        'blogid'=>$siteConfig->ID,
                        'blogname'=>$siteConfig->Title
                    )
                );
    }
    
    /**
     * Handles creation of a new post
     * @param {int} $blog_id Identifier for the current site
     * @param {array} $content Post details
     * @param {int} $publish 0 or 1 depending on whether to publish the post or not
     */
    protected function newPost($blog_id, $content, $publish) {
        $results=$this->extend('newPost', $blog_id, $content, $publish);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
            
            if(count($results)>0) {
                return array_shift($results);
            }
        }
        
        
        if(array_key_exists('custom_fields', $content)) {
            //Ensure the type is an extension of the KapostPage object
            if(!class_exists('Kapost'.$content['custom_fields']['kapost_custom_type']) || !('Kapost'.$content['custom_fields']['kapost_custom_type']=='KapostPage' || is_subclass_of('Kapost'.$content['custom_fields']['kapost_custom_type'], 'KapostPage'))) {
                return $this->httpError(400, _t('KapostService.TYPE_NOT_KNOWN', '_The type "{type}" is not a known type', array('type'=>$content['custom_fields']['kapost_custom_type'])));
            }
            
            $className='Kapost'.$content['custom_fields']['kapost_custom_type'];
        }else {
            //Assume we're creating a page and set the content as such
            $className='KapostPage';
        }
        
        $obj=new $className();
        $obj->Title=$content['title'];
        $obj->Content=$content['description'];
        $obj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
        $obj->KapostChangeType='new';
        $obj->KapostRefID=(array_key_exists('custom_fields', $content) ? $content['custom_fields']['kapost_post_id']:null);
        $obj->ToPublish=$publish;
        $obj->write();
        
        
        //Fallback for tests where the kapost_post_id is missing
        if(!array_key_exists('custom_fields', $content)) {
            $obj->KapostRefID=$className.'_'.$obj->ID;
            $obj->write();
        }
        
        
        //Allow extensions to adjust the new page
        $this->extend('updateNewKapostPage', $obj, $blog_id, $content, $publish);
        
        return (array_key_exists('custom_fields', $content) ? $content['custom_fields']['kapost_post_id']:$className.'_'.$obj->ID);
    }
    
    /**
     * Handles editing of a given post
     * @param {int} $content_id Identifier for the post
     * @param {array} $content Post details
     * @param {int} $publish 0 or 1 depending on whether to publish the post or not
     */
    protected function editPost($content_id, $content, $publish) {
        $results=$this->extend('editPost', $content_id, $content, $publish);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
            
            if(count($results)>0) {
                return array_shift($results);
            }
        }
        
        
        //Ensure the type is an extension of the KapostPage object
        if(array_key_exists('custom_fields', $content) && (!class_exists('Kapost'.$content['custom_fields']['kapost_custom_type']) || !('Kapost'.$content['custom_fields']['kapost_custom_type']=='KapostPage' || is_subclass_of('Kapost'.$content['custom_fields']['kapost_custom_type'], 'KapostPage')))) {
            return $this->httpError(400, _t('KapostService.TYPE_NOT_KNOWN', '_The type "{type}" is not a known type', array('type'=>$content['custom_fields']['kapost_custom_type'])));
        }
        
        
        //Assume we're looking for a page
        //Switch Versioned to stage
        $oldReadingStage=Versioned::current_stage();
        Versioned::set_reading_mode('stage');
        
        $page=SiteTree::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
        
        //Switch Versioned back
        Versioned::set_reading_mode($oldReadingStage);
        
        
        if(!empty($page) && $page!==false && $page->exists()) {
            $className=(array_key_exists('custom_fields', $content) ? 'Kapost'.$content['custom_fields']['kapost_custom_type']:'KapostPage');
            $obj=new $className();
            $obj->Title=$content['title'];
            $obj->Content=$content['description'];
            $obj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
            $obj->KapostChangeType='edit';
            $obj->LinkedPageID=$page->ID;
            $obj->KapostRefID=(array_key_exists('custom_fields', $content) ? $content['custom_fields']['kapost_post_id']:null);
            $obj->ToPublish=$publish;
            $obj->write();
            
            
            //Allow extensions to adjust the new page
            $this->extend('updateEditKapostPage', $obj, $content_id, $content, $publish);
            
            return true;
        }else {
            $kapostObj=KapostObject::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
            if(!empty($kapostObj) && $kapostObj!==false && $kapostObj->exists()) {
                $kapostObj->Title=$content['title'];
                $kapostObj->Content=$content['description'];
                $kapostObj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
                $kapostObj->KapostRefID=(array_key_exists('custom_fields', $content) ? $content['custom_fields']['kapost_post_id']:null);
                $kapostObj->ToPublish=$publish;
                $kapostObj->write();
                
                //Allow extensions to adjust the existing object
                $this->extend('updateEditKapostPage', $kapostObj, $content_id, $content, $publish);
                
                return true;
            }
        }
        
        //Can't find the object so return a 404 code
        return new xmlrpcresp(0, 404, _t('KapostService.INVALID_POST_ID', '_Invalid post ID.'));
    }
    
    /**
     * Gets the details of a post from the system
     * @param {int} $content_id ID of the post in the system
     */
    protected function getPost($content_id) {
        $results=$this->extend('getPost', $content_id);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
            
            if(count($results)>0) {
                return array_shift($results);
            }
        }
        
        
        //Switch Versioned to stage
        $oldReadingStage=Versioned::current_stage();
        Versioned::set_reading_mode('stage');
        
        $page=SiteTree::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
        
        //Switch Versioned back
        Versioned::set_reading_mode($oldReadingStage);
        
        
        if(!empty($page) && $page!==false && $page->exists()) {
            $postMeta=array(
                        'title'=>$page->Title,
                        'description'=>$page->Content,
                        'mt_keywords'=>'',
                        'mt_excerpt'=>$page->MetaDescription,
                        'categories'=>array('ss_page'),
                        'permaLink'=>$page->AbsoluteLink()
                    );
            
            //Allow extensions to modify the page meta
            $results=$this->extend('updatePageMeta', $page);
            if(count($results)>0) {
                for($i=0;$i<count($results);$i++) {
                    $postMeta=$this->mergeResultArray($postMeta, $results[$i]);
                }
            }
            
            return $postMeta;
        }else {
            $kapostObj=KapostObject::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
            if(!empty($kapostObj) && $kapostObj!==false && $kapostObj->exists()) {
                $postMeta=array(
                            'title'=>$kapostObj->Title,
                            'description'=>$kapostObj->Content,
                            'mt_keywords'=>'',
                            'mt_excerpt'=>$kapostObj->MetaDescription,
                            'categories'=>array('ss_page'),
                            'permaLink'=>Controller::join_links(Director::absoluteBaseURL(), 'admin/kapost/KapostObject/EditForm/field/KapostObject/item', $kapostObj->ID, 'edit')
                        );
                
                //Allow extensions to modify the page meta
                $results=$this->extend('updateObjectMeta', $kapostObj);
                if(count($results)>0) {
                    for($i=0;$i<count($results);$i++) {
                        $postMeta=$this->mergeResultArray($postMeta, $results[$i]);
                    }
                }
                
                return $postMeta;
            }
        }
        
        return new xmlrpcresp(0, 404, _t('KapostService.INVALID_POST_ID', '_Invalid post ID.'));
    }
    
    /**
     * Gets the categories
     * @param {int} $blog_id ID of the blog
     * @return {array} Array of categories
     */
    protected function getCategories($blog_id) {
        $categories=array();
        $pageClasses=ClassInfo::subclassesFor('SiteTree');
        foreach($pageClasses as $class) {
            if($class!='SiteTree') {
                $categories[]=array(
                                'categoryId'=>'ss_'.strtolower($class),
                                'categoryName'=>singleton($class)->i18n_singular_name(),
                                'parentId'=>0
                            );
            }
        }
        
        
        
        $results=$this->extend('getCategories', $blog_id);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
            
            if(count($results)>0) {
                for($i=0;$i<count($results);$i++) {
                    $categories=array_merge($categories, $results[$i]);
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Handles media objects from kapost
     * @param {int} $blog_id Site Config related to this content object
     * @param {array} $content Content object to be handled
     * @return {xmlrpcresp} XML-RPC Response object
     */
    protected function newMediaObject($blog_id, $content) {
        $fileName=$content['name'];
        $validator=new Upload_Validator(array('name'=>$fileName));
        $validator->setAllowedExtensions(File::config()->allowed_extensions);
        
        //Verify we have a valid extension
        if($validator->isValidExtension()==false) {
            return $this->httpError(403, _t('KapostService.FILE_NOT_ALLOWED', '_File extension is not allowed'));
        }
        
        
        //Generate default filename
		$nameFilter=FileNameFilter::create();
		$file=$nameFilter->filter($fileName);
		while($file[0]=='_' || $file[0]=='.') {
			$file=substr($file, 1);
		}
		
		$doubleBarrelledExts=array('.gz', '.bz', '.bz2');
		
		$ext="";
		if(preg_match('/^(.*)(\.[^.]+)$/', $file, $matches)) {
			$file=$matches[1];
			$ext=$matches[2];
			
			// Special case for double-barrelled 
			if(in_array($ext, $doubleBarrelledExts) && preg_match('/^(.*)(\.[^.]+)$/', $file, $matches)) {
				$file=$matches[1];
				$ext=$matches[2].$ext;
			}
		}
		
		$origFile=$file;
		
        
		//Find the kapost media folder
		$kapostMediaFolder=Folder::find_or_make($this->config()->kapost_media_folder);
        
		$i = 1;
		while(file_exists($kapostMediaFolder->getFullPath().'/'.$file.$ext)) {
			$i++;
			$oldFile=$file;
			
			if(strpos($file, '.')!==false) {
				$file = preg_replace('/[0-9]*(\.[^.]+$)/', $i.'\\1', $file);
			}else if(strpos($file, '_')!==false) {
				$file=preg_replace('/_([^_]+$)/', '_'.$i, $file);
			}else {
				$file.='_'.$i;
			}

			if($oldFile==$file && $i > 2) {
			    return $this->httpError(500, _t('KapostService.FILE_RENAME_FAIL', '_Could not fix {filename} with {attempts} attempts', array('filename'=>$file.$ext, 'attempts'=>$i)));
			}
		}
        
        //Write the file to the file system
        $f=fopen($kapostMediaFolder->getFullPath().'/'.$file.$ext, 'w');
        fwrite($f, $content['bits']);
        fclose($f);
        
        
        //Write the file to the database
        $className=File::get_class_for_file_extension(substr($ext, 1));
        $obj=new $className();
        $obj->Name=$file.$ext;
        $obj->Title=$file.$ext;
        $obj->FileName=$kapostMediaFolder->getRelativePath().'/'.$file.$ext;
        $obj->ParentID=$kapostMediaFolder->ID;
        
        //If subsites is enabled add it to the correct subsite
        if(File::has_extension('FileSubsites')) {
            $obj->SubsiteID=$blog_id;
        }
        
        $obj->write();
        
        
        $this->extend('updateNewMediaAsset', $blog_id, $content, $obj);
        
        
        return array(
                    'id'=>$obj->ID,
                    'url'=>$obj->getAbsoluteURL()
                );
    }
    
    /**
     * Converts a struct to an associtive array based on the key value pair in the struct
     * @param {array} $struct Input struct to be converted
     * @return {array} Associtive array matching the struct
     */
    final protected function struct_to_assoc($struct) {
        $result=array();
        foreach($struct as $item) {
            if(array_key_exists('key', $item) && array_key_exists('value', $item)) {
                if(array_key_exists($item['key'], $result)) {
                    user_error('Duplicate key detected in struct entry, content overwritten by the last entry: [New: '.print_r($item, true).'] [Previous: '.print_r($result[$item['key']], true).']', E_USER_WARNING);
                }
                
                $result[$item['key']]=$item['value'];
            }else {
                user_error('Key/Value pair not detected in struct entry: '.print_r($item, true), E_USER_NOTICE);
            }
        }
        
        return $result;
    }
    
    /**
     * Merges two arrays, overwriting the keys in the left array with the right array recurrsivly. Meaning that if a value in the right array is it self an array and the key exists in the left array it recurses into it.
     * @param {array} $leftArray Left array to merge into
     * @param {array} $rightArray Right array to merge from
     * @param {int} $depth Recurrsion depth
     * @return {array} Resulting array
     */
    private function mergeResultArray($leftArray, $rightArray, $depth=0) {
        if($depth>10) {
            user_error('Too much recurrsion', E_USER_ERROR);
        }
        
        foreach($rightArray as $key=>$value) {
            if(is_array($value) && array_key_exists($key, $leftArray)) {
                $leftArray[$key]=$this->mergeResultArray($leftArray[$key], $value, $depth+1);
            }else {
                $leftArray[$key]=$value;
            }
        }
        
        return $leftArray;
    }
    
    /**
     * Finds a file record based on the url of the file, this is needed because Kapost doesn't seem to send anything back other than the url in the cms
     * @param {string} $url Absolute url to the file
     * @return {File} Returns the file instance representing the url, or boolean false if it's not found
     */
    public static function find_file_by_url($url) {
        $url=Director::makeRelative($url);
        if($url) {
            $file=File::get()->filter('Filename', Convert::raw2sql($url))->first();
            if(!empty($file) && $file!==false && $file->ID>0) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
	 * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS.
	 * @return {array} Map of permission codes
	 */
	public function providePermissions() {
	    return array(
	               'KAPOST_API_ACCESS'=>array(
	                                           'category'=>'Kapost Bridge',
                                               'name'=>_t('KapostService.PERMISSION_API_ACCESS', '_Kapost API Access'),
	                                           'help'=>_t('KapostService.PERMISSION_API_ACCESS_DESC', '_Access the XML-RPC Endpoint for Kapost to communicate with')
	                                       ),
	            );
	}
}
?>