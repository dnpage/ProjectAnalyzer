<?php

namespace DNPage\ProjectAnalyzer;


class ProjectAnalyzer
{
    protected $dirs = [];
    protected $files = [];
    protected $stats = [];
    protected $total_loc_breakdown = [];

    public function __construct($path)
    {
        $this->dirs = $this->getDirs($path);
        $this->files = $this->getAllFiles($this->dirs);
        $this->calcAllStats();
    }

    public function getDirs($path)
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $this->dirs = [$path];
            foreach ($iterator as $path => $dir) {
                if ($dir->isDir()) {
                    $this->dirs[] = $path;
                }
            }
        }  catch (\UnexpectedValueException $e) {
            throw new \Exception("Unable to analyze path: $path Please verify path");
        }
        return $this->dirs;
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
        foreach (glob($path.'/*.php') as $file_name) {
            $files[] = $file_name;
        }
        return $files;
    }

    public function getAllStats()
    {
        return $this->stats;
    }

    public function getTotalLOCBreakdown()
    {
        return $this->total_loc_breakdown;
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
            if ($stat['Classes'] > 0) {
                $stat['M/C'] = round($stat['Methods'] / $stat['Classes'], 1);
            } else {
                $stat ['M/C'] = '-';
            }
            if ($stat['Methods'] > 0) {
                $stat['LOC/M'] = round($stat['LOC'] / $stat['Methods'], 1);
            } else {
                $stat ['LOC/M'] = '-';
            }
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

        $total_loc = $this->getTotalLOC();

        $total_stats['Name'] = 'Total';
        $total_stats['Lines'] = $total_loc['total'];
        $total_stats['LOC'] = $total_loc['loc'];
        $total_stats['Classes'] = $this->getTotalTokenCount(T_CLASS);
        $total_stats['Methods'] = $this->getTotalTokenCount(T_FUNCTION);
        if ($total_stats['Classes'] > 0) {
            $total_stats['M/C'] = round($total_stats['Methods'] / $total_stats['Classes'], 1);
        } else {
            $total_stats['M/C'] = '-';
        }
        if ($total_stats['Methods'] > 0) {
            $total_stats['LOC/M'] = round($total_stats['LOC']/$total_stats['Methods'], 1);
        } else {
            $total_stats['LOC/M'] = '-';
        }

        return $total_stats;
    }

    private function calcTotalLOCBreakdown($stats)
    {
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
            $ratio = round($test_loc/$code_loc, 2);
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
        $count = 0;
        $blank_line_count = 0;
        foreach ($lines as $line) {
            // exclude blank lines from the overall count
            $line = rtrim($line);
            if ($line == '') {
                $blank_line_count++;
            }
            $count++;
        }

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
}
