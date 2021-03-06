<?php
namespace Splash\Local\Tests;

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

/**
 * @abstract    Local Test Suite - Verify Acces to MultiCompany Objects 
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01MultiCompanyTest extends ObjectsCase {
    
    /**
     * @var array
     */    
    private $ObjectList     = array(); 

    /**
     * @var array
     */    
    private $ObjectCount    = array(); 
        
    /**
     * @abstract    Test Loading of Object that are not on Selected Entity
     * @dataProvider ObjectTypesProvider
     */
    public function testLoadAcces($Sequence, $ObjectType)
    {
        $this->loadLocalTestSequence($Sequence);
        
        //====================================================================//
        //   Enable MultiCompany Mode
        $this->changeMultiCompanyMode(True);
        
        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);
        
        //====================================================================//
        //   Get next Available Object ID from Module  
        $ObjectId = $this->getNextObjectId($ObjectType);
        $this->assertNotEmpty($ObjectId);

        //====================================================================//
        //   Get Readable Object Fields List  
        $Fields = $this->reduceFieldList(Splash::Object($ObjectType)->Fields(), True, False);
        
        //====================================================================//
        //   Execute Action Directly on Module  
        $Allowed = Splash::Object($ObjectType)->Get($ObjectId, $Fields);
        
        //====================================================================//
        //   Verify Response
        $this->assertNotEmpty($Allowed);

        //====================================================================//
        //   Simulate Logged on another Entity
        $this->changeEntityId();
        
        //====================================================================//
        //   Execute Action Directly on Module  
        $Rejected = Splash::Object($ObjectType)->Get($ObjectId, $Fields);
        
        //====================================================================//
        //   Verify Response
        $this->assertFalse($Rejected);
        
        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);
        
    }
    
    /**
     * @abstract    Test Delete of Object that are not on Selected Entity
     * @dataProvider ObjectTypesProvider
     */
    public function testDeleteAccess($Sequence, $ObjectType)
    {
        $this->loadLocalTestSequence($Sequence);
        
        //====================================================================//
        //   Enable MultiCompany Mode
        $this->changeMultiCompanyMode(True);
        
        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);
        
        //====================================================================//
        //   Generate Dummy Object Data (Required Fields Only)   
        $DummyData = $this->PrepareForTesting($ObjectType);
        if ( $DummyData == False ) {
            return True;
        }
        
        //====================================================================//
        //   Create a New Object on Module  
        $ObjectId = Splash::Object($ObjectType)->Set(Null, $DummyData);        
        
        //====================================================================//
        // Lock New Objects To Avoid Action Commit 
        Splash::Object($ObjectType)->Lock($ObjectId);
        
        //====================================================================//
        //   Simulate Logged on another Entity
        $this->changeEntityId();
        
        //====================================================================//
        //   Delete Object on Module  
        $Rejected = Splash::Object($ObjectType)->Delete($ObjectId);
        
        //====================================================================//
        //   Verify Response
        $this->assertFalse($Rejected);

        //====================================================================//
        //   Simulate Logged on Main Entity
        $this->changeEntityId(1);
        
        //====================================================================//
        //   Delete Object on Module  
        $Allowed = Splash::Object($ObjectType)->Delete($ObjectId);
        
        //====================================================================//
        //   Verify Response
        $this->assertTrue($Allowed);
        
    }    

    /**
     * @abstract    Simulate MultiCompany Mode 
     */
    public function changeMultiCompanyMode($State = False)
    {
        global $db, $conf;
        
        //====================================================================//
        // Check Dolibarr Version Is Compatible 
        if ( Splash::Local()->DolVersionCmp("5.0.0") < 0 ) {  
            $this->markTestSkipped('This Feature is Not Implemented on Current Dolibarr Release.');
        }
        
        dolibarr_set_const($db,"MAIN_MODULE_MULTICOMPANY"   , ($State?1:0) ,'chaine',0,'',$conf->entity);    
    }
    
    /**
     * @abstract    Simulate Change of MultiCompany Entity 
     */
    public function changeEntityId($EntityId = 10)
    {
        global $conf, $db, $user;
        
        //====================================================================//
        // Check MultiCompany Module 
        $this->assertTrue(Splash::Local()->isMultiCompany());
        
        //====================================================================//
        // Switch Entity
        $conf->entity   =   (int)   $EntityId;
        $conf->setValues($db);
        $user->entity   =   $conf->entity;

        return $conf->entity;
        
    }

    public function getNextObjectId($ObjectType)
    {
        //====================================================================//
        //   If Object List Not Loaded  
        if ( !isset($this->ObjectList[$ObjectType]) ) {
            
            //====================================================================//
            //   Get Object List from Module  
            $List = Splash::Object($ObjectType)->ObjectsList();

            //====================================================================//
            //   Get Object Count
            $this->ObjectCount[$ObjectType] = $List["meta"]["current"];
            
            //====================================================================//
            //   Remove Meta Datats form Objects List
            unset($List["meta"]);
            
            //====================================================================//
            //   Convert ArrayObjects
            if (is_a($List, "ArrayObject")) {
                $this->ObjectList[$ObjectType] = $List->getArrayCopy();
            } else {
                $this->ObjectList[$ObjectType] = $List;                
            }
        }
        
        //====================================================================//
        //   Verify Objects List is Not Empty  
        if ( $this->ObjectCount[$ObjectType] <= 0 ) {
            $this->markTestSkipped('No Objects in Database.');
            return False;
        }
        
        //====================================================================//
        //   Return First Object of List
        $NextObject = array_shift($this->ObjectList[$ObjectType]);
        return $NextObject["id"];
    }   
    
    public function VerifyTestIsAllowed($ObjectType)
    {
        $Definition = Splash::Object($ObjectType)->Description();

        $this->assertNotEmpty($Definition);
        //====================================================================//
        //   Verify Create is Allowed
        if ( !$Definition["allow_push_created"] ) {
            return False;
        }    
        //====================================================================//
        //   Verify Delete is Allowed
        if ( !$Definition["allow_push_deleted"] ) {
            return False;
        }    
        return True;
    }
    
    public function PrepareForTesting($ObjectType)
    {
        //====================================================================//
        //   Verify Test is Required   
        if ( !$this->VerifyTestIsAllowed($ObjectType) ) {
            return False;
        }
        
        //====================================================================//
        // Read Required Fields & Prepare Dummy Data
        //====================================================================//
        $Write          = False;
        $Fields         = Splash::Object($ObjectType)->Fields();
        foreach ( $Fields as $Key => $Field) {
            
            //====================================================================//
            // Skip Non Required Fields
            if ( !$Field->required ) {
                unset( $Fields[$Key] );
            }
            //====================================================================//
            // Check if Write Fields
            if ( $Field->write ) {   
                $Write = True;
            }            
        }
        
        //====================================================================//
        // If No Writable Fields 
        if ( !$Write ) {
            return False;
        } 
        
        //====================================================================//
        // Lock New Objects To Avoid Action Commit 
        Splash::Object($ObjectType)->Lock();
        
        //====================================================================//
        // Clean Objects Commited Array 
        Splash::$Commited = Array();
        
        return $this->fakeObjectData($Fields);
    }
    
}
