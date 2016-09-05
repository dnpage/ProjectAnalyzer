<?php

use DNPage\ProjectAnalyzer\ProjectAnalyzer;

class ProjectAnalyzerTest extends \PHPUnit_Framework_TestCase
{

    public function testProjectAnalyzerISInstantiated()
    {
        $pa = new ProjectAnalyzer(dirname(__DIR__). '/FakeProject1');
        $this->assertInstanceOf(ProjectAnalyzer::class, $pa);
    }

    public function testEmptyStatsForNonExistentPath()
    {
        $pa = new ProjectAnalyzer('nonexistent/path');
        $stats = $pa->getAllStats();
        $this->assertInternalType('array', $stats);
        $this->assertEquals(1, count($stats));
        $this->assertEquals('Total', $stats[0]['Name']);
        $this->assertEquals(0, $stats[0]['Lines']);
    }

    public function testSettingOfWhiteList()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->setWhiteList('src,tests');
        $white_list = $pa->getWhiteList();

        $rp = realpath(__DIR__. '/../FakeProject1');
        $expected_results = [
            $rp . '/src',
            $rp .  '/tests'
        ];
        $this->assertInternalType('array', $white_list);
        $this->assertEquals($expected_results, $white_list);
    }

    public function testSettingOfBlackList()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->setBlackList('vendor');
        $black_list = $pa->getBlackList();

        $rp = realpath(__DIR__. '/../FakeProject1');
        $expected_results = [
            $rp . '/vendor'
        ];
        $this->assertInternalType('array', $black_list);
        $this->assertEquals($expected_results, $black_list);
    }

    public function testSettingOfBlackListWithEmptyPath()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->setBlackList('');
        $black_list = $pa->getBlackList();
        $expected_results = [];
        $this->assertInternalType('array', $black_list);
        $this->assertEquals($expected_results, $black_list);
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
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject2'));
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(3, count($stats));
    }

    public function testReturnsStatsForProjectWithNoClassesOrMethods()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject2'));
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(3, count($stats));
    }


    public function testReturnsStatsForProjectWithOnlyTestDir()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject4'));
        $stats = $pa->getAllStats();

        $this->assertInternalType('array', $stats);
        $this->assertEquals(2, count($stats));
    }


    public function testReturnsArrayOfDirectories()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $dirs = $pa->getDirs(realpath(__DIR__. '/../FakeProject1'));

        $this->assertInternalType('array', $dirs);
        $this->assertEquals(1, count($dirs));
    }


    public function testReturnsArrayOfDirectoriesWithUsingWhiteList()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__ . '/../'));
        $pa->setWhiteList('tests');
        $dirs = $pa->getDirs(realpath(__DIR__ . '/../'));
        $this->assertInternalType('array', $dirs);
        $this->assertEquals(1, count($dirs));
    }

    public function testReturnsArrayOfDirectoriesWithUsingBlackList()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__ . '/../'));
        $pa->setBlackList('vendor');
        $dirs = $pa->getDirs(realpath(__DIR__ . '/../'));

        $this->assertInternalType('array', $dirs);
        $this->assertEquals(9, count($dirs));
    }

    public function testExceptionIsGeneratedWhenBadPathForDirs()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $dirs = $pa->getDirs('nonexistent/path');
        $this->assertEmpty($dirs);

    }

    public function testReturnsArrayOfFiles()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $dirs = $pa->getDirs(realpath(__DIR__. '/../FakeProject1'));
        $files = $pa->getAllFiles($dirs);
        $this->assertInternalType('array', $files);
        $this->assertEquals(3, count($files[$dirs[0]]));
    }


    public function testReturnsLOCForAFile()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $loc = $pa->getLOC(realpath(__DIR__. '/../FakeProject1/FakeDoubleClass.php'));
        $sub_total = $loc['blank'] + $loc['comment'] + $loc['loc'];
        $this->assertEquals(5, $loc['blank']);
        $this->assertEquals(7, $loc['comment']);
        $this->assertEquals(15, $loc['loc']);
        $this->assertEquals(27, $loc['total']);
        $this->assertEquals($sub_total, $loc['total']);
    }

    public function testReturnsTotalLOCForAllFiles()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->getAllStats();
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
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->getAllStats();
        $loc_breakdown = $pa->getTotalLOCBreakdown();
        $this->assertInternalType('array', $loc_breakdown);
        $this->assertArrayHasKey('code_loc', $loc_breakdown);
        $this->assertArrayHasKey('test_loc', $loc_breakdown);
        $this->assertArrayHasKey('code_to_test_ratio', $loc_breakdown);
    }

    public function testReturnsClassCountForAFileWithOneClassDeclaration()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $class_count = $pa->getTokenCount(realpath(__DIR__. '/../FakeProject1/FakeClass.php'), T_CLASS);
        $this->assertEquals(1, $class_count);
    }


    public function testReturnsClassCountForAFileWithTwoClassDeclarations()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $class_count = $pa->getTokenCount(realpath(__DIR__. '/../FakeProject1/FakeDoubleClass.php'), T_CLASS);
        $this->assertEquals(2, $class_count);
    }


    public function testReturnsClassCountForAFileWithAnInterfaceDeclarations()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $class_count = $pa->getTokenCount(realpath(__DIR__. '/../FakeProject1/FakeInterface.php'), T_CLASS);
        $this->assertEquals(0, $class_count);
    }

    public function testReturnsTotalClassCountForAllFiles()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->getAllStats();
        $total_class_count = $pa->getTotalTokenCount(T_CLASS);
        $this->assertGreaterThan(0, $total_class_count);
    }


    public function testReturnsMethodCountForOneClass()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $method_count = $pa->getTokenCount(realpath(__DIR__. '/../FakeProject1/FakeClass.php'), T_FUNCTION);
        $this->assertEquals(1, $method_count);
    }

    public function testReturnsTotalMethodCountForAllFiles()
    {
        $pa = new ProjectAnalyzer(realpath(__DIR__. '/../FakeProject1'));
        $pa->getAllStats();
        $total_method_count = $pa->getTotalTokenCount(T_FUNCTION);
        $this->assertGreaterThan(0, $total_method_count);
    }

}