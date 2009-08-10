<?php
// needed libraries
require_once dirname(__FILE__) . '/lib/at/at.php';

// needed version
if (version_compare(PHP_VERSION, '5.3.0') < 0) die('I need at least PHP 5.3.0.');

// templates file
$templates_file = 'templates.at'; // default
if ($_SERVER['argc'] > 1) {
    $templates_file = $_SERVER['argv'][1];
}

// get templates
$fnname_ret_all = function ($tokens) {
    $ret = '';
    foreach ($tokens as $token) $ret .= $token['content'];
    return array(trim($ret), array());
};

$templates = array();

at()
    ->fnname($fnname_ret_all)
    ->notfound(function ($block, $name) use ($fnname_ret_all, &$templates) {
        $name = preg_split('~\s*<\s*~', $name);
        $extending = count($name) > 1;
        if (!$extending) $name += array(NULL);
        list($name, $parent) = $name;

        $blocks = array();
        $text = at()
            ->fnname($fnname_ret_all)
            ->notfound(function ($block, $name) use ($extending, &$blocks) {
                if (!$extending) {
                    $blocks[] = array($name, $block);
                    return chr(0);
                } else $blocks[$name] = $block;
            })
            ->run($block);
        $text = explode(chr(0), $text);

        if (!$extending) {
            $template = array();
            for ($i = 0, $stop = count($text) - 1; $i < $stop; ++$i) {
                $template[] = array(NULL, $text[$i]);
                $template[] = array($blocks[$i][0], $blocks[$i][1]);
            }
            $template[] = array(NULL, $text[$i]);

        } else {
            if (!isset($templates[$parent])) die("I want my mommy! \n        -- $name");
            $template = $templates[$parent];

            foreach ($template as &$block) {
                if (in_array($block[0], array_keys($blocks))) {
                    $block = array($block[0], $blocks[$block[0]]);
                }
            }
        }

        $templates[$name] = $template;
    })
    ->run(file_get_contents($templates_file));


array_walk($templates, function (&$template) {
    $new = array();
    foreach ($template as $_) {
        if (is_array($_[1])) $new = array_merge($new, $_[1]);
        else $new[] = $_[1];
    }
    $template = $new;
});

// terms
$terms_dir = dirname(__FILE__) . '/terms/';
$terms = array();
$letters = array_fill_keys(
    array_map(function ($i) { return chr($i); }, range(ord('a'), ord('z'))),
    array()
);
foreach (glob($terms_dir . '*') as $_) {
    $term = substr($_, strlen($terms_dir));
    $content = file_get_contents($_);
    $definitions = explode("\n%\n", $content);
    $terms[$term] = array();

    foreach ($definitions as $definition) {
        if (trim($definition) === '') continue;
        $data = array();

        foreach (explode("\n", $definition) as $line) {
            list($k, $v) = explode(':', $line, 2);
            $data[trim($k)] = trim($v);
        }

        $terms[$term][$data['definition']] = array(
            'translation' => $data['translation'],
            'comment' => isset($data['comment']) ? $data['comment'] : NULL
        );
    }

    $letters[substr($term, 0, 1)][$term] = $terms[$term];
}

// generate
$echo = function ($block) {
    return (string) (isset($GLOBALS['@' . trim($block[0])]) ? $GLOBALS['@' . trim($block[0])] : NULL);
};

$site_dir = dirname(__FILE__) . '/site/';

// index
$at = at();
$at
    ->fn('', $echo)
    ->fn('letters', function ($block) use ($at, $letters) {
        $ret = '';

        foreach ($letters as $letter => $terms) {
            $GLOBALS['@letter'] = strtoupper($letter);
            $GLOBALS['@terms'] = $terms;
            $ret .= $at->run($block);
        }

        return $ret;
    })
    ->fn('terms', function ($block) use ($at) {
        $ret = '';

        foreach ($GLOBALS['@terms'] as $term => $_) {
            $GLOBALS['@term'] = $term;
            $ret .= $at->run($block);
        }

        return $ret;
    });
file_put_contents($site_dir . 'index.html', ltrim($at->run($templates['index'])));
unset($templates['index']);

// terms
foreach ($terms as $term => $definitions) {
    $GLOBALS['@term'] = $term;

    $at = at();
    $at
        ->fn('', $echo)
        ->fn('definitions', function ($block) use($at, $definitions) {
            $ret = '';

            foreach ($definitions as $definition => $_) {
                foreach ($_ + array('definition' => $definition) as $k => $v) $GLOBALS['@' . $k] = $v;
                $ret .= $at->run($block);
            }

            return $ret;
        });

    file_put_contents($site_dir . $term . '.html', ltrim($at->run($templates['term'])));
}
unset($templates['term']);

// others

foreach ($templates as $name => $template) {
    if (strpos($name, '.') === FALSE) $name .= '.html';
    file_put_contents($site_dir . $name, ltrim(implode('', $template)));
}
