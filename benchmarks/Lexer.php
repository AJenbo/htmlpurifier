<?php

// emulates inserting a dir called HTMLPurifier into your class dir
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');

require_once 'HTMLPurifier/Lexer/DirectLex.php';
require_once 'HTMLPurifier/Lexer/PEARSax3.php';

$LEXERS = array(
    'DirectLex' => new HTMLPurifier_Lexer_DirectLex(),
    'PEARSax3'  => new HTMLPurifier_Lexer_PEARSax3()
);

if (version_compare(PHP_VERSION, '5', '>=')) {
    require_once 'HTMLPurifier/Lexer/DOMLex.php';
    $LEXERS['DOMLex'] = new HTMLPurifier_Lexer_DOMLex();
}

// PEAR
require_once 'Benchmark/Timer.php'; // to do the timing
require_once 'Text/Password.php'; // for generating random input

// custom class to aid unit testing
class RowTimer extends Benchmark_Timer
{
    
    var $name;
    
    function RowTimer($name, $auto = false) {
        $this->name = htmlentities($name);
        $this->Benchmark_Timer($auto);
    }
    
    function getOutput() {

        $total  = $this->TimeElapsed();
        $result = $this->getProfiling();
        $dashes = '';
        
        $out = '<tr>';
        
        $out .= "<td>{$this->name}</td>";
        
        foreach ($result as $k => $v) {
            if ($v['name'] == 'Start' || $v['name'] == 'Stop') continue;
            
            //$perc = (($v['diff'] * 100) / $total);
            //$tperc = (($v['total'] * 100) / $total);
            
            $out .= '<td align="right">' . $v['diff'] . '</td>';
            
            //$out .= '<td align="right">' . number_format($perc, 2, '.', '') .
            //       '%</td>';
            
        }
        
        $out .= '</tr>';
        
        return $out;
    }
}

function print_lexers() {
    global $LEXERS;
    $first = true;
    foreach ($LEXERS as $key => $value) {
        if (!$first) echo ' / ';
        echo htmlspecialchars($key);
        $first = false;
    }
}

function do_benchmark($name, $document) {
    global $LEXERS;
    
    $timer = new RowTimer($name);
    $timer->start();
    
    foreach($LEXERS as $key => $lexer) {
        $tokens = $lexer->tokenizeHTML($document);
        $timer->setMarker($key);
    }
    
    $timer->stop();
    $timer->display();
}

?>
<html>
<head>
<title>Benchmark: <?php print_lexers(); ?></title>
</head>
<body>
<h1>Benchmark: <?php print_lexers(); ?></h1>
<table border="1">
<tr><th>Case</th><?php
foreach ($LEXERS as $key => $value) {
    echo '<th>' . htmlspecialchars($key) . '</th>';
}
?></tr>
<?php

// ************************************************************************** //

// sample of html pages

$dir = 'samples/Lexer';
$dh  = opendir($dir);
while (false !== ($filename = readdir($dh))) {
    
    if (strpos($filename, '.html') !== strlen($filename) - 5) continue;
    $document = file_get_contents($dir . '/' . $filename);
    do_benchmark("File: $filename", $document);
    
}

// crashers, caused infinite loops before

$snippets = array();
$snippets[] = '<a href="foo>';
$snippets[] = '<a "=>';

foreach ($snippets as $snippet) {
    do_benchmark($snippet, $snippet);
}

// random input

$random = Text_Password::create(80, 'unpronounceable', 'qwerty <>="\'');

do_benchmark('Random input', $random);

?></table>

<?php

echo '<div>Random input was: ' .
  '<span colspan="4" style="font-family:monospace;">' .
  htmlspecialchars($random) . '</span></div>';

?>


</body></html>