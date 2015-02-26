<?php
class KapostService extends Controller {
    private static $authenticator_class='MemberAuthenticator';
    private static $authenticator_username_field='Email';
    
    private static $exposed_methods=array(
                                        'blogger.getUsersBlogs',
                                        'metaWeblog.newPost',
                                        'metaWeblog.editPost',
                                        'metaWeblog.getPost',
                                        'metaWeblog.getCategories',
                                        //'metaWeblog.newMediaObject', //Not implemented
                                        //'metaWeblog.getPreview' //Not implemented
                                    );
    
    /**
     * 
     */
    public function index() {
        $methods=array_fill_keys($this->config()->exposed_methods, array('function'=>array($this, 'handleRPCMethod')));
        
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
     * 
     */
    public function handleRPCMethod(xmlrpcmsg $request) {
        $username=$request->getParam(1)->getval();
        $password=$request->getParam(2)->getval();
        
        if($this->authenticate($username, $password)) {
            $method=str_replace(array('blogger.', 'metaWeblog.'), '', $request->methodname);
            
            if((!in_array('blogger.'.$method, $this->config()->exposed_methods) && !in_array('metaWeblog.'.$method, $this->config()->exposed_methods)) || !method_exists($this, $method)) {
                return $this->httpError(403, 'Action "'.$method.'" isn\'t allowed on class Kapost Service.');
            }
            
            
            //Pack params into call to method if they are not the authentication parameters
            $params=array();
            for($i=0;$i<$request->getNumParams();$i++) {
                if($i!=1 && $i!=2) {
                    $params[]=$request->getParam($i)->getval();
                }
            }
            
            
            //Call the method
            $response=call_user_func_array(array($this, $method), $params);
            if($response instanceof xmlrpcresp) {
                return $response; //Response is already encoded so return
            }
            
            //Encode the response
            return php_xmlrpc_encode($response);
        }
        
        
        return $this->httpError(401, _t('Member.ERRORWRONGCRED', 'The provided details don\'t seem to be correct. Please try again.'));
    }
    
    /**
     *
     */
    protected function authenticate($username, $password) {
        $authenticator=$this->config()->authenticator_class;
        
        $member=$authenticator::authenticate(array(
                                                $this->config()->authenticator_username_field=>$username,
                                                'Password'=>$password
                                            ));
        
        return (!empty($member) && $member!==false && $member->exists()==true);
    }
    
    /**
     *
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
     * 
     */
    protected function newPost($blog_id, $content, $publish) {
        $results=$this->extend('editPost', $content_id, $content, $publish);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
        
            return array_shift($results);
        }
        
        
        //Assume we're creating a page and set the content as such
        $obj=new KapostPage();
        $obj->Title=$content['title'];
        $obj->Content=$content['description'];
        $obj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
        $obj->KapostChangeType='new';
        $obj->ToPublish=$publish;
        $obj->write();
        
        return 'kapost_'.$obj->ID;
    }
    
    /**
     *
     */
    protected function editPost($content_id, $content, $publish) {
        $results=$this->extend('editPost', $content_id, $content, $publish);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
        
            return array_shift($results);
        }
        
        
        //Assume we're looking for a page
        //Switch Versioned to stage
        $oldReadingStage=Versioned::current_stage();
        Versioned::set_reading_mode('stage');
        
        $page=SiteTree::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
        
        //Switch Versioned back
        Versioned::set_reading_mode($oldReadingStage);
        
        
        if(!empty($page) && $page!==false && $page->exists()) {
            $obj=new KapostPage();
            $obj->Title=$content['title'];
            $obj->Content=$content['description'];
            $obj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
            $obj->KapostChangeType='edit';
            $obj->LinkedPageID=$page->ID;
            $obj->ToPublish=$publish;
            $obj->write();
            
            return true;
        }else {
            $kapostObj=KapostObject::get()->byID(intval(preg_replace('/^([^0-9]*)/', '', $content_id)));
            if(!empty($kapostObj) && $kapostObj!==false && $kapostObj->exists()) {
                $kapostObj->Title=$content['title'];
                $kapostObj->Content=$content['description'];
                $kapostObj->MetaDescription=(array_key_exists('mt_excerpt', $content) ? $content['mt_excerpt']:null);
                $kapostObj->ToPublish=$publish;
                $kapostObj->write();
                
                return true;
            }
        }
        
        //Can't find the object so return a 404 code
        return new xmlrpcresp(0, 404, 'Invalid post ID.');
    }
    
    /**
     *
     */
    protected function getPost($content_id) {
        $results=$this->extend('getPost', $content_id);
        if($results && is_array($results)) {
            $results=array_filter($results, function($v) {return !is_null($v);});
            
            return array_shift($results);
        }
        
        
        //Switch Versioned to stage
        $oldReadingStage=Versioned::current_stage();
        Versioned::set_reading_mode('stage');
        
        $page=SiteTree::get()->filter('KapostRefID', Convert::raw2sql($content_id))->first();
        
        //Switch Versioned back
        Versioned::set_reading_mode($oldReadingStage);
        
        
        if(!empty($page) && $page!==false && $page->exists()) {
            return array(
                        'title'=>$page->Title,
                        'description'=>$page->Content,
                        'mt_keywords'=>$page->MetaKeywords,
                        'mt_excerpt'=>$page->MetaDescription,
                        'categories'=>array('ss_page'),
                        'permaLink'=>$page->AbsoluteLink()
                    );
        }else {
            $kapostObj=KapostObject::get()->byID(intval(preg_replace('/^([^0-9]*)/', '', $content_id)));
            if(!empty($kapostObj) && $kapostObj!==false && $kapostObj->exists()) {
                return array(
                            'title'=>$kapostObj->Title,
                            'description'=>$kapostObj->Content,
                            'mt_keywords'=>$kapostObj->MetaKeywords,
                            'mt_excerpt'=>$kapostObj->MetaDescription,
                            'categories'=>array('ss_page'),
                            'permaLink'=>Controller::join_links(Director::absoluteBaseURL(), 'admin/kapost/KapostObject/EditForm/field/KapostObject/item', $kapostObj->ID, 'edit')
                        );
            }
        }
        
        return new xmlrpcresp(0, 404, 'Invalid post ID.');
    }
    
    /**
     * 
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
}
?>