<?php
/**
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package Debug
 * @subpackage Tests
 */

function testDebugMessageErrorHandler( $errno, $errstr, $errfile, $errline )
{
    ezcDebug::debugHandler( $errno, $errstr, $errfile, $errline );
    return true;
}

/**
 * @package Debug
 * @subpackage Tests
 */
class ezcDebugMessageTest extends ezcTestCase
{
    public static function suite()
    {
        return new \PHPUnit\Framework\TestSuite( __CLASS__ );
    }

    public function setUp(): void
    {
        $dbg = ezcDebug::getInstance();
        $dbg->reset();
        $dbg->setOutputFormatter( new TestReporter() );

        $dbg->getEventLog()->source   = 'DefaultSource';
        $dbg->getEventLog()->category = 'DefaultCategory';
        
        set_error_handler( 'testDebugMessageErrorHandler' );
    }

    public function tearDown(): void
    {
        restore_error_handler();
    }
    
    public function testParseMessage()
    {
        trigger_error( '[Source, Category] 23: Message', E_USER_WARNING );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            'Message',
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'Category',
            $out[0][0]->category
        );
        $this->assertEquals(
            'Source',
            $out[0][0]->source
        );
    }

    public function testParseMessageNoMessage()
    {
        trigger_error( '[Source, Category] 23:', E_USER_WARNING );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            false,
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'Category',
            $out[0][0]->category
        );
        $this->assertEquals(
            'Source',
            $out[0][0]->source
        );
    }
    
    public function testParseMessageNoSource()
    {
        trigger_error( '[Category] 23: Message', E_USER_WARNING );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            'Message',
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'Category',
            $out[0][0]->category
        );
        $this->assertEquals(
            'DefaultSource',
            $out[0][0]->source
        );
    }
    
    public function testParseMessageNoCategoryNoSource()
    {
        trigger_error( '23: Message', E_USER_WARNING );

        $out = ezcDebug::getInstance()->generateOutput();
        
        $this->assertEquals(
            'Message',
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'DefaultCategory',
            $out[0][0]->category
        );
        $this->assertEquals(
            'DefaultSource',
            $out[0][0]->source
        );
    }
    
    public function testParseMessageSeverityNotice()
    {
        trigger_error( '[Source, Category] 23: Message', E_USER_NOTICE );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            'Message',
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'Category',
            $out[0][0]->category
        );
        $this->assertEquals(
            'Source',
            $out[0][0]->source
        );
    }
    
    public function testParseMessageSeverityError()
    {
        // PHP 8.4 deprecates trigger_error() with E_USER_ERROR and reports
        // that deprecation first; hand the handler the user error directly.
        ezcDebug::debugHandler( E_USER_ERROR, '[Source, Category] 23: Message', __FILE__, __LINE__ );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            'Message',
            $out[0][0]->message
        );
        $this->assertEquals(
            23,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'Category',
            $out[0][0]->category
        );
        $this->assertEquals(
            'Source',
            $out[0][0]->source
        );
    }
    
    public function testParseMessageUnknownSeverity()
    {
        // trigger_error() with an unknown level (42) made PHP 7 raise this
        // E_WARNING instead; PHP 8 throws a ValueError and calls no handler.
        // Hand the handler the warning PHP 7 raised: a message PHP itself
        // emits, without "[Source, Category] N:", and not a user level.
        ezcDebug::debugHandler( E_WARNING, 'Invalid error type specified', __FILE__, __LINE__ );

        $out = ezcDebug::getInstance()->generateOutput();

        $this->assertEquals(
            'Invalid error type specified',
            $out[0][0]->message
        );
        $this->assertEquals(
            false,
            $out[0][0]->verbosity
        );
        $this->assertEquals(
            'DefaultCategory',
            $out[0][0]->category
        );
        $this->assertEquals(
            'DefaultSource',
            $out[0][0]->source
        );
    }
}
?>
