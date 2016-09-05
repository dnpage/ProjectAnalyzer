<?php

namespace DNPage\ProjectAnalyzer;


class ProjectAnalyzer
{
    protected $path;
    protected $white_list = [];
    protected $black_list = [];
    protected $dirs = [];
    protected $files = [];
    protected $stats = [];
    protected $total_loc_breakdown = [];

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function setWhiteList($list)
    {
        $this->white_list = $this->convertListToPathsArray($list, $this->path);
    }

    public function setBlackList($list)
    {
        $this->black_list = $this->convertListToPathsArray($list, $this->path);
    }

    public function getWhiteList()
    {
        return $this->white_list;
    }

    public function getBlackList()
    {
        return $this->black_list;
    }

    public function getAllStats()
    {
        $this->dirs = $this->getDirs($this->path);
        $this->files = $this->getAllFiles($this->dirs);
        $this->calcAllStats();
        return $this->stats;
    }


    public function getTotalLOCBreakdown()
    {
        return $this->total_loc_breakdown;
    }

    public function getDirs($path)
    {
        $dirs = $this->glob_recursive($path);
        if (!empty($this->white_list)) {
            $dirs = array_filter($dirs, [$this, 'isInWhiteList']);
        }
        if (!empty($this->black_list)) {
            $dirs = array_filter($dirs, [$this, 'isNotInBlackList']);
        }
        return $dirs;
    }


    public function getAllFiles($dirs)
    {
        $this->files = [];
        foreach ($dirs as $dir) {
            $this->files[$dir] = $this->getFiles($dir);
        }
        return $this->files;
    }

    public function getFiles($path)
    {
        $files = [];
        foreach (glob($path . '/*.php') as $file_name) {
            $files[] = $file_name;
        }
        return $files;
    }


    private function calcAllStats()
    {
        $stats = [];
        foreach ($this->files as $dir => $files) {
            $stat = [];
            $stat['Name'] = basename($dir);
            if ($this->isNamedTestDir($dir)) {
                $stat['Name'] .= ' (unit tests)';
            }
            $stat['Lines'] = 0;
            $stat['LOC'] = 0;
            $stat['Classes'] = 0;
            $stat['Methods'] = 0;
            foreach ($files as $file) {
                $loc = $this->getLOC($file);
                $stat['Lines'] += $loc['total'];
                $stat['LOC'] += $loc['loc'];
                $class_count = $this->getTokenCount($file, T_CLASS);
                $stat['Classes'] += $class_count;
                $method_count = $this->getTokenCount($file, T_FUNCTION);
                $stat['Methods'] += $method_count;
            }
            $stat['M/C'] = $this->formatMethodsPerClass($stat['Methods'], $stat['Classes']);
            $stat['LOC/M'] = $this->formatLOCPerMethod($stat['LOC'], $stat['Methods']);

            if ($stat['Lines'] > 0) {
                $stats[] = $stat;
            }
        }

        $stats[] = $this->calcTotalStats();

        $this->stats = $stats;

        $this->total_loc_breakdown = $this->calcTotalLOCBreakdown($this->stats);
    }

    private function calcTotalStats()
    {
        $total_stats = [];

        $total_loc = $this->getTotalLOC();

        $total_stats['Name'] = 'Total';
        $total_stats['Lines'] = $total_loc['total'];
        $total_stats['LOC'] = $total_loc['loc'];
        $total_stats['Classes'] = $this->getTotalTokenCount(T_CLASS);
        $total_stats['Methods'] = $this->getTotalTokenCount(T_FUNCTION);
        $total_stats['M/C'] = $this->formatMethodsPerClass($total_stats['Methods'], $total_stats['Classes']);
        $total_stats['LOC/M'] = $this->formatLOCPerMethod($total_stats['LOC'], $total_stats['Methods']);

        return $total_stats;
    }

    private function calcTotalLOCBreakdown($stats)
    {
        $loc_breakdown = [];

        $code_loc = 0;
        $test_loc = 0;
        $loc_breakdown['code_loc'] = 0;
        $loc_breakdown['test_loc'] = 0;
        foreach ($stats as $stat) {
            if ($stat['Name'] != 'Total') {
                if ($this->isNamedTestDir($stat['Name'])) {
                    $test_loc += $stat['LOC'];
                } else {
                    $code_loc += $stat['LOC'];
                }
            }
        }
        $loc_breakdown['code_loc'] = $code_loc;
        $loc_breakdown['test_loc'] = $test_loc;
        if ($code_loc > 0) {
            $ratio = round($test_loc / $code_loc, 2);
            $loc_breakdown['code_to_test_ratio'] = '1:' . $ratio;
        } else {
            $loc_breakdown['code_to_test_ratio'] = '';
        }

        return $loc_breakdown;
    }

    public function getTotalLOC()
    {
        $total_count = 0;
        $total_loc = 0;
        $total_blank_line_count = 0;
        $total_comment_line_count = 0;
        foreach ($this->files as $dir => $files) {
            foreach ($files as $file) {
                $loc = $this->getLOC($file);
                $total_count += $loc['total'];
                $total_loc += $loc['loc'];
                $total_blank_line_count += $loc['blank'];
                $total_comment_line_count += $loc['comment'];
            }
        }
        return [
            'blank' => $total_blank_line_count,
            'comment' => $total_comment_line_count,
            'loc' => $total_loc,
            'total' => $total_count
        ];
    }

    public function getLOC($file_name)
    {
        $lines = file($file_name);
        $count = count($lines);
        $trimmed_count = count(array_filter(array_map('rtrim', $lines)));
        $blank_line_count = $count - $trimmed_count;

        $php_code = file_get_contents($file_name);
        $tokens = token_get_all($php_code);
        $comments = array_filter($tokens, function($token) {
            return $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT;
        });

        $comment_line_count = array_reduce($comments, function(&$result, $item) {
            return $result += count(explode("\n", trim($item[1])));
        }, 0);

        $loc = $count - $comment_line_count - $blank_line_count;

        return [
            'blank' => $blank_line_count,
            'comment' => $comment_line_count,
            'loc' => $loc,
            'total' => $count
        ];
    }

    public function getTotalTokenCount($token)
    {
        $total_token_count = 0;
        foreach ($this->files as $dir => $files) {
            foreach ($files as $file) {
                $token_count = $this->getTokenCount($file, $token);
                $total_token_count += $token_count;
            }
        }
        return $total_token_count;
    }

    public function getTokenCount($file, $token) {

        $php_code = file_get_contents($file);
        $items = [];
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == $token
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING) {

                $token_name = $tokens[$i][1];
                $items[] = $token_name;
            }
        }
        return count($items);
    }

    public function isNamedTestDir($path)
    {
        $path = strtolower($path);
        return strpos($path, 'test') !== false;
    }

    private function formatMethodsPerClass($num_methods, $num_classes)
    {
        if ($num_classes > 0) {
            $methods_per_class = round($num_methods / $num_classes, 1);
        } else {
            $methods_per_class = '-';
        }
        return $methods_per_class;
    }

    private function formatLOCPerMethod($num_loc, $num_methods)
    {
        if ($num_methods > 0) {
            $loc_per_method = round($num_loc / $num_methods, 1);
        } else {
            $loc_per_method = '-';
        }
        return $loc_per_method;
    }


    private function convertListToPathsArray($list, $path)
    {
        $list = array_filter(explode(',', $list));
        $list = array_map(function($val) use($path) {
            return $path . '/' . $val;
        }, $list);
        return $list;
    }


    private function glob_recursive($path)
    {
        if (!is_dir($path)) {
            return [];
        }
        $dirs[] = $path;
        foreach (glob($path.'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $dirs = array_merge($dirs, $this->glob_recursive($dir));
        }
        return $dirs;
    }

    private function isInWhiteList($path)
    {
        $in_white_list = false;
        foreach ($this->white_list as $good_dir) {
            if (strpos($path, $good_dir) !== false) {
                $in_white_list = true;
                break;
            }
        }
        return $in_white_list;
    }

    private function isNotInBlackList($path)
    {
        $in_black_list = false;
        foreach ($this->black_list as $good_dir) {
            if (strpos($path, $good_dir) !== false) {
                $in_black_list = true;
                break;
            }
        }
        return !$in_black_list;
    }
}
