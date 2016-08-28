<?php

use DNPage\ProjectAnalyzer\ProjectAnalyzer;

class ProjectAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \DNPage\ProjectAnalyzer\ProjectAnalyzer
     */
    protected $project_analyzer;


    public function setup()
    {
        $this->project_analyzer = new ProjectAnalyzer('/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer');
    }

    public function testClassExists()
    {
        $this->assertInstanceOf(ProjectAnalyzer::class, $this->project_analyzer);
    }

    public function testReturnsArrayOfDirectories()
    {
        $dirs = $this->project_analyzer->getDirs();

        $this->assertInternalType('array', $dirs);
        $this->assertEquals(2, count($dirs));
    }

    public function testReturnsArrayOfFiles()
    {
        $files = $this->project_analyzer->getAllFiles();
        $this->assertInternalType('array', $files);
    }

    public function testDirectoryCount()
    {
        $dir_count = $this->project_analyzer->getDirectoryCount();
        $this->assertGreaterThan(0, $dir_count);
    }

    public function testFileCount()
    {
        $file_count = $this->project_analyzer->getFileCount();
        $this->assertGreaterThan(0, $file_count);
    }

    public function testReturnsLOCForAFile()
    {
        $loc = $this->project_analyzer->getLOC(
            '/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer/Tests/FakeDoubleClass.php'
        );
        $sub_total = $loc['blank'] + $loc['comment'] + $loc['loc'];
        $this->assertEquals(6, $loc['blank']);
        $this->assertEquals(8, $loc['comment']);
        $this->assertEquals(16, $loc['loc']);
        $this->assertEquals(30, $loc['total']);
        $this->assertEquals($sub_total, $loc['total']);
    }

    public function testReturnsTotalLOCForAllFiles()
    {
        $total_loc = $this->project_analyzer->getTotalLoc();
        $sub_total = $total_loc['blank'] + $total_loc['comment'] + $total_loc['loc'];
        $this->assertGreaterThan(0, $total_loc['blank']);
        $this->assertGreaterThan(0, $total_loc['comment']);
        $this->assertGreaterThan(0, $total_loc['loc']);
        $this->assertGreaterThan(0, $total_loc['total']);
        $this->assertEquals($sub_total, $total_loc['total']);
    }


    public function testReturnsClassCountForAFileWithOneClassDeclaration()
    {
        $class_count = $this->project_analyzer->getTokenCount(
            '/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer/Tests/FakeClass.php',
            T_CLASS
        );
        $this->assertEquals(1, $class_count);
    }


    public function testReturnsClassCountForAFileWithTwoClassDeclarations()
    {
        $class_count = $this->project_analyzer->getTokenCount(
            '/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer/Tests/FakeDoubleClass.php',
            T_CLASS
        );
        $this->assertEquals(2, $class_count);
    }


    public function testReturnsClassCountForAFileWithAnInterfaceDeclarations()
    {
        $class_count = $this->project_analyzer->getTokenCount(
            '/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer/Tests/FakeInterface.php',
            T_CLASS
        );
        $this->assertEquals(0, $class_count);
    }

    public function testReturnsTotalClassCountForAllFiles()
    {
        $total_class_count = $this->project_analyzer->getTotalTokenCount(T_CLASS);
        $this->assertGreaterThan(0, $total_class_count);
    }


    public function testReturnsMethodCountForOneClass()
    {
        $method_count = $this->project_analyzer->getTokenCount(
            '/var/www/phpdesignpatterns/src/Lib/ProjectAnalyzer/Tests/FakeClass.php',
            T_FUNCTION
        );
        $this->assertEquals(1, $method_count);
    }

    public function testReturnsTotalMethodCountForAllFiles()
    {
        $total_method_count = $this->project_analyzer->getTotalTokenCount(T_FUNCTION);
        $this->assertGreaterThan(0, $total_method_count);
    }




}