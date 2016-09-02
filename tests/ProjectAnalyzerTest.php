<?php

use DNPage\ProjectAnalyzer\ProjectAnalyzer;

class ProjectAnalyzerTest extends \PHPUnit_Framework_TestCase
{
//    /**
//     * @var \DNPage\ProjectAnalyzer\ProjectAnalyzer
//     */
//    protected $project_analyzer;
//
//
//    public function setup()
//    {
//        $this->project_analyzer = new ProjectAnalyzer(dirname(__DIR__). '/src');
//    }

    public function testClassExists()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $this->assertInstanceOf(ProjectAnalyzer::class, $pa);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to analyze path: nonexistent/path Please verify path
     */
    public function testFailedInstantiationOfClass()
    {
        $pa = new ProjectAnalyzer('nonexistent/path');

    }




    public function testReturnsStatsForProject()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(2, count($stats));
    }


    public function testReturnsStatsForProjectWithSourceAndTestDirs()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject2');
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(3, count($stats));
    }

    public function testReturnsStatsForProjectWithNoClassesOrMethods()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject3');
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(2, count($stats));
    }


    public function testReturnsStatsForProjectWithOnlyTestDir()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject4');
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(2, count($stats));
    }


    public function testReturnsArrayOfDirectories()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $dirs = $pa->getDirs(dirname(__DIR__) . '/FakeProject1');

        $this->assertInternalType('array', $dirs);
        $this->assertEquals(1, count($dirs));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to analyze path: nonexistent/path Please verify path
     */
    public function testExceptionIsGeneratedWhenBadPathForDirs()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $dirs = $pa->getDirs('nonexistent/path');

    }

    public function testReturnsArrayOfFiles()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $dirs = $pa->getDirs(dirname(__DIR__). '/FakeProject1');
        $files = $pa->getAllFiles($dirs);
        $this->assertInternalType('array', $files);
        $this->assertEquals(3, count($files[$dirs[0]]));
    }


    public function testReturnsLOCForAFile()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $loc = $pa->getLOC(dirname(__DIR__) . '/FakeProject1/FakeDoubleClass.php');
        $sub_total = $loc['blank'] + $loc['comment'] + $loc['loc'];
        $this->assertEquals(5, $loc['blank']);
        $this->assertEquals(7, $loc['comment']);
        $this->assertEquals(15, $loc['loc']);
        $this->assertEquals(27, $loc['total']);
        $this->assertEquals($sub_total, $loc['total']);
    }

    public function testReturnsTotalLOCForAllFiles()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $total_loc = $pa->getTotalLoc();
        $sub_total = $total_loc['blank'] + $total_loc['comment'] + $total_loc['loc'];
        $this->assertGreaterThan(0, $total_loc['blank']);
        $this->assertGreaterThan(0, $total_loc['comment']);
        $this->assertGreaterThan(0, $total_loc['loc']);
        $this->assertGreaterThan(0, $total_loc['total']);
        $this->assertEquals($sub_total, $total_loc['total']);
    }

    public function testReturnsTotalLOCBreakdown()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $loc_breakdown = $pa->getTotalLOCBreakdown();
        $this->assertInternalType('array', $loc_breakdown);
        $this->assertArrayHasKey('code_loc', $loc_breakdown);
        $this->assertArrayHasKey('test_loc', $loc_breakdown);
        $this->assertArrayHasKey('code_to_test_ratio', $loc_breakdown);
    }

    public function testReturnsClassCountForAFileWithOneClassDeclaration()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $class_count = $pa->getTokenCount(dirname(__DIR__) . '/FakeProject1/FakeClass.php', T_CLASS);
        $this->assertEquals(1, $class_count);
    }


    public function testReturnsClassCountForAFileWithTwoClassDeclarations()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $class_count = $pa->getTokenCount(dirname(__DIR__) . '/FakeProject1/FakeDoubleClass.php', T_CLASS);
        $this->assertEquals(2, $class_count);
    }


    public function testReturnsClassCountForAFileWithAnInterfaceDeclarations()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $class_count = $pa->getTokenCount(dirname(__DIR__) . '/FakeProject1/FakeInterface.php', T_CLASS);
        $this->assertEquals(0, $class_count);
    }

    public function testReturnsTotalClassCountForAllFiles()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $total_class_count = $pa->getTotalTokenCount(T_CLASS);
        $this->assertGreaterThan(0, $total_class_count);
    }


    public function testReturnsMethodCountForOneClass()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $method_count = $pa->getTokenCount(dirname(__DIR__) . '/FakeProject1/FakeClass.php', T_FUNCTION);
        $this->assertEquals(1, $method_count);
    }

    public function testReturnsTotalMethodCountForAllFiles()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $total_method_count = $pa->getTotalTokenCount(T_FUNCTION);
        $this->assertGreaterThan(0, $total_method_count);
    }

}