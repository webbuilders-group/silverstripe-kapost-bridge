<?php
class KapostServiceTest extends FunctionalTest {
    const USER_AGENT='Kapost XMLRPC::Client';
    
    private static $configured=false;
    private static $_fixtureFactory;
    private static $_fixtures;
    
    private $exposed_methods=array(
                                    'blogger.getUsersBlogs',
                                    'metaWeblog.newPost',
                                    'metaWeblog.editPost',
                                    'metaWeblog.getPost',
                                    'metaWeblog.getCategories',
                                    'metaWeblog.newMediaObject',
                                    'kapost.getPreview'
                                );
    
    /**
     * Initializes the database, we do it here so we don't loose our information we need a sequential testing environment
     */
    public function setUp() {
        parent::setUp();
        
        
        //Remove all Service extensions (keeps the tests sane)
        $extensions=Object::get_extensions('KapostService');
        if(!empty($extensions)) {
            foreach($extensions as $extension) {
                KapostService::remove_extension($extension);
            }
        }
        
        
        if(self::$configured==false) {
            $prefix=(defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX:'ss_');
            $fixtureFile='KapostServiceTest.yml';
            
            // Set up fixture
            if($fixtureFile || $this->usesDatabase || !self::using_temp_db()) {
                if(substr(DB::getConn()->currentDatabase(), 0, strlen($prefix) + 5)
                != strtolower(sprintf('%stmpdb', $prefix))) {
            
                    self::create_temp_db();
                }
            
                singleton('DataObject')->flushCache();
                    
                self::empty_temp_db();
                    
                foreach($this->requireDefaultRecordsFrom as $className) {
                    $instance = singleton($className);
                    if (method_exists($instance, 'requireDefaultRecords')) $instance->requireDefaultRecords();
                    if (method_exists($instance, 'augmentDefaultRecords')) $instance->augmentDefaultRecords();
                }
            
                if($fixtureFile) {
                    $pathForClass = $this->getCurrentAbsolutePath();
                    $fixtureFiles = (is_array($fixtureFile)) ? $fixtureFile : array($fixtureFile);
            
                    $i = 0;
                    foreach($fixtureFiles as $fixtureFilePath) {
                        // Support fixture paths relative to the test class, rather than relative to webroot
                        // String checking is faster than file_exists() calls.
                        $isRelativeToFile = (strpos('/', $fixtureFilePath) === false
                                || preg_match('/^\.\./', $fixtureFilePath));
            
                        if($isRelativeToFile) {
                            $resolvedPath = realpath($pathForClass . '/' . $fixtureFilePath);
                            if($resolvedPath) $fixtureFilePath = $resolvedPath;
                        }
                            
                        $fixture = Injector::inst()->create('YamlFixture', $fixtureFilePath);
                        $fixture->writeInto($this->getFixtureFactory());
                        $this->fixtures[] = $fixture;
            
                        // backwards compatibility: Load first fixture into $this->fixture
                        if($i == 0) $this->fixture = $fixture;
                        $i++;
                    }
                    
                    
                    self::$_fixtureFactory=$this->getFixtureFactory();
                    self::$_fixtures=$this->fixtures;
                }
                
                $this->logInWithPermission("ADMIN");
            }
            
            self::$configured=true;
        }else {
            $this->fixtureFactory=self::$_fixtureFactory;
            $this->fixtures=self::$_fixtures;
        }
    }
    
    /**
     * Ensures the list methods response matches the expected response which is that it contains atleast the methods defined in the core
     */
    public function testListMethods() {
        $response=$this->call_service('list-methods');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Verify that the default methods are in the array
        foreach($this->exposed_methods as $method) {
            $this->assertContains($method, $responseData);
        }
    }
    
    /**
     * Tests to see if an unknown id response is correct
     */
    public function testGetPostUnknownID() {
        //Call the api and get the response
        $response=$this->call_service('get-post');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure we had a 404
        $this->assertEquals(404, $rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
    }
    
    /**
     * Test Creation of a new post
     */
    public function testNewPost() {
        $response=$this->call_service('new-post');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Make sure the response id matches the kapost_post_id sent in the request
        $this->assertEquals('55241502b83cb77d1f0004d9', $responseData);
        
        
        //Verify the audit object is present
        $obj=KapostObject::get()->filter('KapostRefID', '55241502b83cb77d1f0004d9')->first();
        $this->assertNotEmpty($obj);
        $this->assertNotEquals(false, $obj);
        $this->assertTrue($obj->exists());
        
        
        //Make sure the type change type is new
        $this->assertEquals('new', $obj->KapostChangeType);
    }
    
    /**
     * Tests get post returns a good response since we're written the post
     */
    public function testGetPostKnownID() {
        $response=$this->call_service('get-post');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure we didn't get a 404
        $this->assertNotEquals(404, $rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Verify all keys are present, we're not checking the values just that they're not empty
        $this->assertArrayHasKey('title', $responseData);
        $this->assertNotEmpty($responseData['title']);
        
        $this->assertArrayHasKey('description', $responseData);
        $this->assertNotEmpty($responseData['description']);
        
        $this->assertArrayHasKey('permaLink', $responseData);
        $this->assertNotEmpty($responseData['permaLink']);
        
        $this->assertArrayHasKey('mt_excerpt', $responseData);
        
        $this->assertArrayHasKey('mt_keywords', $responseData);
        
        $this->assertArrayHasKey('categories', $responseData);
        
        $this->assertArrayHasKey('custom_fields', $responseData);
        $this->assertNotEmpty($responseData['custom_fields']);
        
        
        
        //Parse the custom fields into a key, value pair
        $custom_fields=array();
        foreach($responseData['custom_fields'] as $field) {
            $custom_fields[$field['key']]=$field['value'];
        }
        
        
        //Test the values for SS_Title and SS_MetaDescription
        $this->assertArrayHasKey('SS_Title', $custom_fields);
        $this->assertNotEmpty($custom_fields['SS_Title']);
        
        $this->assertArrayHasKey('SS_MetaDescription', $custom_fields);
        $this->assertNotEmpty($custom_fields['SS_MetaDescription']);
    }
    
    /**
     * Tests to ensure the edit post request works correctly
     */
    public function testEditPost() {
        $response=$this->call_service('edit-post');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Make sure the response is true meaning the edit worked
        $this->assertTrue($responseData);
        
        
        //Verify the audit object is present
        $obj=KapostObject::get()->filter('KapostRefID', '55241502b83cb77d1f0004d9')->first();
        $this->assertNotEmpty($obj);
        $this->assertNotEquals(false, $obj);
        $this->assertTrue($obj->exists());
        
        
        //Make sure the type change type is new
        $this->assertEquals('new', $obj->KapostChangeType);
        
        
        //Make sure the title actually changed
        $this->assertEquals('Full Test Title 2', $obj->Title);
    }
    
    /**
     * Tests handling of new media objects
     */
    public function testNewMediaObject() {
        $response=$this->call_service('new-media-asset');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Validate the response
        $this->assertArrayHasKey('id', $responseData);
        $this->assertNotEmpty($responseData['id']);
        
        $this->assertArrayHasKey('url', $responseData);
        $this->assertNotEmpty($responseData['url']);
        
        
        //See if the data record was found
        $file=File::get()->byID(intval($responseData['id']));
        $this->assertNotEmpty($file);
        $this->assertNotEquals(false, $file);
        $this->assertTrue($file->exists());
        
        
        //See if the file was created
        $this->assertFileExists($file->getFullPath());
        
        
        
        /** Call again to make sure the rename worked **/
        $response=$this->call_service('new-media-asset');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Validate the response
        $this->assertArrayHasKey('id', $responseData);
        $this->assertNotEmpty($responseData['id']);
        
        $this->assertArrayHasKey('url', $responseData);
        $this->assertNotEmpty($responseData['url']);
        
        
        //See if the data record was found
        $file2=File::get()->byID(intval($responseData['id']));
        $this->assertNotEmpty($file2);
        $this->assertNotEquals(false, $file2);
        $this->assertTrue($file2->exists());
        
        
        //See if the file was created
        $this->assertFileExists($file2->getFullPath());
        
        
        //Check to see if the file has been renamed
        $this->assertNotEquals($file->Filename, $file2->Filename);
    }
    
    /**
     * Tests handling of overwriting duplicate media objects
     */
    public function testOverwriteDuplicateMediaObject() {
        KapostService::config()->duplicate_assets='overwrite';
        
        $response=$this->call_service('new-media-asset');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Validate the response
        $this->assertArrayHasKey('id', $responseData);
        $this->assertNotEmpty($responseData['id']);
        
        $this->assertArrayHasKey('url', $responseData);
        $this->assertNotEmpty($responseData['url']);
        
        
        //See if the data record was found
        $file=File::get()->byID(intval($responseData['id']));
        $this->assertNotEmpty($file);
        $this->assertNotEquals(false, $file);
        $this->assertTrue($file->exists());
        
        
        //See if the file was created
        $this->assertFileExists($file->getFullPath());
        
        
        //Make sure the file name didn't change
        $this->assertEquals('test.png', basename($file->Filename));
    }
    
    /**
     * Tests handling of ignoring duplicate media objects
     */
    public function testIgnoreDuplicateMediaObject() {
        KapostService::config()->duplicate_assets='ignore';
        
        $response=$this->call_service('new-media-asset');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Validate the response
        $this->assertEquals(10409, $rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
    }
    
    /**
     * Tests to see if the preview functionality is working correctly
     */
    public function testPreviewObject() {
        $response=$this->call_service('get-preview');
        
        
        //Make sure the response is a 200
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the content type is text/xml
        $this->assertEquals('text/xml', $response->getHeader('Content-Type'));
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Parse data
        $rpcResponse=$this->parseRPCResponse($response->getBody());
        
        
        //Make sure the fault code is 0
        $this->assertEmpty($rpcResponse->faultCode(), 'Fault: '.$rpcResponse->faultString());
        
        
        //Process the response data
        $responseData=$rpcResponse->value();
        
        
        //Make sure the id is present and is not empty
        $this->assertArrayHasKey('id', $responseData);
        $this->assertNotEmpty($responseData['id']);
        
        
        //Make sure the url is present and is not empty
        $this->assertArrayHasKey('url', $responseData);
        $this->assertNotEmpty($responseData['url']);
        
        
        //Make the url relative
        $url=Director::makeRelative($responseData['url']);
        
        //Test the preview
        $response=$this->get($url);
        
        
        //Ensure we recieved a 200 back
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Make sure the response contains Kapost Tracking code this should be a good guage to see if the response is correct
        $this->assertContains('_kaq.push([2, "zzzzzzzzz", "zzzzzzzzz"]);', $response->getBody());
        
        
        //Verify the object is set to preview
        $kapostObj=KapostObject::get()->filter('KapostRefID', '55241502b83cb77d1f0004d9')->first();
        $this->assertEquals(1, $kapostObj->IsKapostPreview);
    }
    
    /**
     * Tests to see if the expired token returns a 404 as expected
     */
    public function testPreviewExpiredToken() {
        //Generate the preview token
        $token=new KapostPreviewToken();
        $token->Code='testcode';
        $token->write();
        
        
        //Modify the created date of the token, we do it this way because write will remove it 
        DB::query('UPDATE "KapostPreviewToken" '.
                'SET "Created"=\''.date('Y-m-d H:i:s', strtotime('-'.(KapostService::config()->preview_expiry+1).' minutes')).'\''.
                'WHERE "ID"='.$token->ID);
        
        
        //Get the preview
        $response=$this->get('kapost-service/preview/55241502b83cb77d1f0004d9?auth=testcode');
        
        
        //Ensure we recieved a 404 back
        $this->assertEquals(404, $response->getStatusCode());
    }
    
    /**
     * Calls the api and returns the response
     * @param {string} $mockRequest Mock Request to load
     * @return {SS_HTTPResponse} Response Object
     */
    protected function call_service($mockRequest) {
        return $this->post('kapost-service', array(), array('User-Agent'=>self::USER_AGENT), null, file_get_contents(dirname(__FILE__).'/mock_requests/'.$mockRequest.'.xml'));
    }
    
    /**
     * Parses the response from the api
     * @param {string} $body XML Response
     * @return {xmlrpcresp} XML RPC Response Object
     */
    final protected function parseRPCResponse($body) {
        $xmlmsg=new xmlrpcmsg('');
        
        return $xmlmsg->parseResponse($body, true, 'phpvals');
    }
}
?>