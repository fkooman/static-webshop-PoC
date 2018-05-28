<?php

$baseDir = \dirname(__DIR__);
require_once \sprintf('%s/vendor/autoload.php', $baseDir);

use Michelf\MarkdownExtra;

$parser = new MarkdownExtra();
$loader = new Twig_Loader_Filesystem(\sprintf('%s/views', $baseDir));
$twig = new Twig_Environment($loader, ['strict_variables' => true]);

$config = include \sprintf('%s/config/config.php', $baseDir);

foreach ($config['pages'] as $pageId => $pageName) {
    // generate the pages from markdown
    $htmlContent = $parser->transform(\file_get_contents(\sprintf('%s/pages/%s.md', $baseDir, $pageId)));

    $renderedTemplate = $twig->render(
        'staticPage.twig',
        [
            'menuItems' => $config['pages'],
            'pageTitle' => $pageName,
            'pageContent' => $htmlContent,
        ]
    );

    // write page
    \file_put_contents(
        \sprintf('%s/output/%s.html', $baseDir, $pageId),
        $renderedTemplate
    );
}

$shoppingMenu = [];
$prodCat = [];

foreach ($config['products'] as $productId => $productInfo) {
    $prodCat[$productInfo['cat']][] = $productId;
    $menuTreeItems = \explode(':', $productInfo['cat']);
    build_tree($shoppingMenu, $menuTreeItems);
}

$ulMenu = '<ul>'.build_ul($shoppingMenu).'</ul>';

foreach ($prodCat as $k => $prodItems) {
    $renderedTemplate = $twig->render(
        'catPage.twig',
        [
            'menuItems' => $config['pages'],
            'pageTitle' => 'Shop',
            'ulMenu' => $ulMenu,
            'prodItems' => $prodItems,
        ]
    );

    // write page
    \file_put_contents(
        \sprintf('%s/output/%s.html', $baseDir, \str_replace(':', '_', $k)),
        $renderedTemplate
    );
}

foreach ($config['products'] as $productId => $productInfo) {
    $renderedTemplate = $twig->render(
        'productPage.twig',
        [
            'menuItems' => $config['pages'],
            'pageTitle' => $productInfo['name'],
            'ulMenu' => $ulMenu,
            'productInfo' => $productInfo,
        ]
    );

    // write page
    \file_put_contents(
        \sprintf('%s/output/%d.html', $baseDir, $productId),
        $renderedTemplate
    );
}

function build_tree(&$a, $subArray)
{
    $first = \array_shift($subArray);
    if (0 === \count($subArray)) {
        // empty
        $a[] = $first;

        return;
    }
    if (!\array_key_exists($first, $a)) {
        $a[$first] = [];
    }
    build_tree($a[$first], $subArray);
}

function build_ul(array $m, $parentList = '')
{
    if ($m === \array_values($m)) {
        // flat array, no more sub categories
        $x = '';
        foreach ($m as $item) {
            $x .= '<li><a href="'.$parentList.'_'.$item.'.html">'.$item.'</a></li>';
        }

        return $x;
    }

    $o = '';
    foreach ($m as $k => $v) {
        if (empty($parentList)) {
            $href = $k.'.html';
            $o .= '<li><a href="'.$href.'">'.$k.'</a><ul> '.build_ul($v, $k).'</ul></li>';
        } else {
            $href = $parentList.'_'.$k.'.html';
            $o .= '<li><a href="'.$href.'">'.$k.'</a><ul> '.build_ul($v, $parentList.'_'.$k).'</ul></li>';
        }
    }

    return $o;
}
