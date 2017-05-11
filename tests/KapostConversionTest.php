<?php
class KapostConversionTest extends FunctionalTest {
    private static $configured=false;
    private static $_fixtureFactory;
    private static $_fixtures;
    
    
    /**
     * @var KapostTestController
     */
    protected $testController;
    
    
    /**
     * Adds the extra data objects if the SiteTree class exists
     */
    public function setUpOnce() {
        if(class_exists('SiteTree')) {
            $this->extraDataObjects=array(
                                        'KapostConversionTest_KapostTestPage',
                                        'KapostConversionTest_TestPage'
                                    );
        }
        
        parent::setUpOnce();
    }
    
    /**
     * Initializes the database, we do it here so we don't loose our information we need a sequential testing environment
     */
    public function setUp() {
        parent::setUp();
        
        
        if(!class_exists('SiteTree')) {
            $this->markTestSkipped('CMS not installed skipping test');
            return;
        }
        
        
        //Remove all extensions (keeps the tests sane)
        $extensions=Object::get_extensions('KapostGridFieldDetailForm_ItemRequest');
        if(!empty($extensions)) {
            foreach($extensions as $extension) {
                KapostGridFieldDetailForm_ItemRequest::remove_extension($extension);
            }
        }
        
        
        $this->testController=new KapostTestController();
        
        
        if(self::$configured==false) {
            $prefix=(defined('SS_DATABASE_PREFIX') ? SS_DATABASE_PREFIX:'ss_');
            $fixtureFile='KapostConversionTest.yml';
    
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
     * Tests conversion of new pages to the actual staged page
     */
    public function testConvertNewPage() {
        if(!class_exists('SiteTree')) {
            $this->markTestSkipped('CMS not installed skipping test');
            return;
        }
        
        //Ensure we're an admin
        $this->logInWithPermission('ADMIN');
        
        
        //Get the kapost object
        $kapostObj=$this->objFromFixture('KapostPage', 'newpage');
        
        
        //Build the url of the request
        $url=Controller::join_links(
                                    $this->testController->TestForm()->Fields()->dataFieldByName('TestGrid')->Link('item'),
                                    $kapostObj->ID,
                                    'ConvertObjectForm'
                                );
        
        
        //Send the mock request
        $testData=array(
                        'ConvertMode'=>'NewPage',
                        'ParentPageID'=>0,
                        'action_doConvertObject'=>'Test'
                    );
        
        $response=$this->post($url, $testData);
        
        
        //Verify a 200 response
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Reset versioned
        Versioned::reset();
        
        
        //Check to see the page got converted
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Verify the fields are correct
        $this->assertEquals($kapostObj->Title, $page->Title);
        $this->assertEquals($kapostObj->Content, $page->Content);
        $this->assertEquals($kapostObj->MenuTitle, $page->MenuTitle);
        $this->assertEquals($kapostObj->MetaDescription, $page->MetaDescription);
    }
    
    /**
     * Tests conversion of edits for a page to the actual staged page
     */
    public function testConvertReplacePage() {
        if(!class_exists('SiteTree')) {
            $this->markTestSkipped('CMS not installed skipping test');
            return;
        }
        
        //Ensure we're an admin
        $this->logInWithPermission('ADMIN');
        
        
        //Get the kapost object
        $kapostObj=$this->objFromFixture('KapostPage', 'editpage');
        
        
        //Build the url of the request
        $url=Controller::join_links(
                                    $this->testController->TestForm()->Fields()->dataFieldByName('TestGrid')->Link('item'),
                                    $kapostObj->ID,
                                    'ConvertObjectForm'
                                );
        
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Send the mock request
        $testData=array(
                        'ConvertMode'=>'ReplacePage',
                        'ReplacePageID'=>$page->ID,
                        'action_doConvertObject'=>'Test'
                    );
        
        $response=$this->post($url, $testData);
        
        
        //Verify a 200 response
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Reset versioned
        Versioned::reset();
        
        
        //Check to see the page got converted
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Verify the fields are correct
        $this->assertEquals($kapostObj->Title, $page->Title);
        $this->assertEquals($kapostObj->Content, $page->Content);
        $this->assertEquals($kapostObj->MenuTitle, $page->MenuTitle);
        $this->assertEquals($kapostObj->MetaDescription, $page->MetaDescription);
        
        
        //Make sure it was published per direction
        $page=Versioned::get_by_stage('SiteTree', 'Live')->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
    }
    
    /**
     * Tests conversion of new pages to the actual staged page with a parent
     */
    public function testConvertNewPageParent() {
        if(!class_exists('SiteTree')) {
            $this->markTestSkipped('CMS not installed skipping test');
            return;
        }
        
        //Ensure we're an admin
        $this->logInWithPermission('ADMIN');
        
        
        //Get the kapost object
        $kapostObj=$this->objFromFixture('KapostPage', 'newpage2');
        
        //Get the parent page
        $parentPage=$this->objFromFixture('Page', 'parentpage');
        
        
        //Build the url of the request
        $url=Controller::join_links(
                                    $this->testController->TestForm()->Fields()->dataFieldByName('TestGrid')->Link('item'),
                                    $kapostObj->ID,
                                    'ConvertObjectForm'
                                );
        
        
        //Send the mock request
        $testData=array(
                        'ConvertMode'=>'NewPage',
                        'ParentPageID'=>$parentPage->ID,
                        'action_doConvertObject'=>'Test'
                    );
        
        $response=$this->post($url, $testData);
        
        
        //Verify a 200 response
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Reset versioned
        Versioned::reset();
        
        
        //Check to see the page got converted
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Verify the fields are correct
        $this->assertEquals($parentPage->ID, $page->ParentID);
    }
    
    /**
     * Tests conversion of edits for a page to the actual staged page
     */
    public function testConvertReplaceCustomPage() {
        if(!class_exists('SiteTree')) {
            $this->markTestSkipped('CMS not installed skipping test');
            return;
        }
        
        //Ensure we're an admin
        $this->logInWithPermission('ADMIN');
        
        
        //Get the test image
        $image=$this->objFromFixture('Image', 'testimage');
        
        //If the file doesn't exist create it
        if(!file_exists($image->getFullPath())) {
            if(!file_exists(dirname($image->getFullPath()))) {
                mkdir(dirname($image->getFullPath()));
            }
            
            touch($image->getFullPath());
        }
        
        
        //Get the kapost object
        $kapostObj=$this->objFromFixture('KapostConversionTest_KapostTestPage', 'editcustomtypepage');
        
        
        //Build the url of the request
        $url=Controller::join_links(
                                    $this->testController->TestForm()->Fields()->dataFieldByName('TestGrid')->Link('item'),
                                    $kapostObj->ID,
                                    'ConvertObjectForm'
                                );
        
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Send the mock request
        $testData=array(
                        'ConvertMode'=>'ReplacePage',
                        'ReplacePageID'=>$page->ID,
                        'action_doConvertObject'=>'Test'
                    );
        
        $response=$this->post($url, $testData);
        
        
        //Verify a 200 response
        $this->assertEquals(200, $response->getStatusCode());
        
        
        //Reset versioned
        Versioned::reset();
        
        
        //Check to see the page got converted
        $page=SiteTree::get()->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Verify the fields are correct
        $this->assertEquals($kapostObj->Title, $page->Title);
        $this->assertEquals($kapostObj->Content, $page->Content);
        $this->assertEquals($kapostObj->MenuTitle, $page->MenuTitle);
        $this->assertEquals($kapostObj->MetaDescription, $page->MetaDescription);
        
        
        //Verify the image was correctly set
        $this->assertEquals($image->ID, $page->TestImageID);
        
        
        //Make sure it was published per direction
        $page=Versioned::get_by_stage('SiteTree', 'Live')->filter('KapostRefID', $kapostObj->KapostRefID)->first();
        $this->assertNotEmpty($page);
        $this->assertNotEquals(false, $page);
        $this->assertTrue($page->exists());
        
        
        //Remove the test image
        unlink($image->getFullPath());
    }
}

class KapostTestController extends Controller implements TestOnly {
    private static $allowed_actions=array(
                                        'TestForm'
                                    );
    
    /**
     * Mock test form used for checking the conversion process
     */
    public function TestForm() {
        $gridConfig=GridFieldConfig_RecordEditor::create();
        $gridConfig->getComponentByType('GridFieldDetailForm')->setItemRequestClass('KapostGridFieldDetailForm_ItemRequest');
        
        $fields=new FieldList(
                            new GridField('TestGrid', 'TestGrid', KapostObject::get(), $gridConfig)
                        );
        
        return new Form($this, 'TestForm', $fields, new FieldList());
    }
}

if(!class_exists('SiteTree')) {
    return;
}

class KapostConversionTest_KapostTestPage extends KapostPage implements TestOnly {
    private static $has_one=array(
                                'TestImage'=>'Image'
                            );
    
    
    /**
     * Gets the destination class when converting to the final object
     * @return string Class to convert to
     */
    public function getDestinationClass() {
        return 'KapostConversionTest_TestPage';
    }
}

class KapostConversionTest_TestPage extends Page implements TestOnly {
    private static $has_one=array(
                                'TestImage'=>'Image'
                            );
}
?>