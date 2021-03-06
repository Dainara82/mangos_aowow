<?php
require_once('includes/allitems.php');
require_once('includes/allspells.php');
if(!$AoWoWconf['disable_comments'])
    require_once('includes/allcomments.php');

$smarty->config_load($conf_file, 'itemset');

$id = intval($podrazdel);

$cache_key = cache_key($id);

if(!$itemset = load_cache(ITEMSET_PAGE, $cache_key))
{
    unset($itemset);

    $row = $DB->selectRow("SELECT * FROM ?_itemset WHERE itemsetID=? LIMIT 1", $id);
    if($row)
    {
        $itemset = array();
        $classmask = 262144;
        $itemset['entry'] = $row['itemsetID'];
        $itemset['name'] = $row['name_loc'.$_SESSION['locale']];
        $itemset['minlevel'] = 255;
        $itemset['maxlevel'] = 0;
        $itemset['count'] = 0;
        $itemset['reqlvl'] = 0;
        $x = 0;
        $itemset['pieces'] = array();
        for($j=1;$j<=10;$j++)
        {
            if($row['item'.$j])
            {
                $itemset['pieces'][$itemset['count']] = array();
                $itemset['pieces'][$itemset['count']] = iteminfo($row['item'.$j]);

                if($itemset['pieces'][$itemset['count']]['level'] < $itemset['minlevel'])
                    $itemset['minlevel'] = $itemset['pieces'][$itemset['count']]['level'];

                if($itemset['pieces'][$itemset['count']]['level'] > $itemset['maxlevel'])
                    $itemset['maxlevel'] = $itemset['pieces'][$itemset['count']]['level'];

                if($itemset['pieces'][$itemset['count']]['reqlevel'] > $itemset['reqlvl'])
                    $itemset['reqlvl'] = $itemset['pieces'][$itemset['count']]['reqlevel'];

                if($itemset['pieces'][$itemset['count']]['classes'] < $classmask)
                    $classmask = $itemset['pieces'][$itemset['count']]['classes'];

                if($itemset['pieces'][$itemset['count']]['classs'] == 4 && $itemset['pieces'][$itemset['count']]['subclass'])
                    $itemset['type'] = $itemset['pieces'][$itemset['count']]['subclass'];

                $itemset['count']++;
            }
        }
        $itemset['classes'] = classes($classmask);
        $itemset['type'] = armor($itemset['type']);
        $itemset['spells'] = array();
        for($j=1;$j<=8;$j++)
            if($row['spell'.$j])
            {
                $itemset['spells'][$x] = array();
                $itemset['spells'][$x]['entry'] = $row['spell'.$j];
                $itemset['spells'][$x]['tooltip'] = spell_desc($row['spell'.$j]);
                $itemset['spells'][$x]['bonus'] = $row['bonus'.$j];
                $x++;
            }
        for($i=0;$i<=$x-1;$i++)
            for($j=$i;$j<=$x-1;$j++)
                if($itemset['spells'][$j]['bonus'] < $itemset['spells'][$i]['bonus'])
                {
                    UnSet($tmp);
                    $tmp = $itemset['spells'][$i];
                    $itemset['spells'][$i] = $itemset['spells'][$j];
                    $itemset['spells'][$j] = $tmp;
                }
    }
    save_cache(ITEMSET_PAGE, $cache_key, $itemset);
}
$smarty->assign('itemset', $itemset);

global $page;
$page = array(
    'Mapper' => false,
    'Book' => false,
    'Title' => $itemset['name'].' - '.$smarty->get_config_vars('Item_Sets'),
    'tab' => 0,
    'type' => 4,
    'typeid' => $itemset['entry'],
    'path' => '[0, 2]',
    'comment' => true
);

// Комментарии
if($AoWoWconf['disable_comments'])
    $page['comment'] = false;
else
    $smarty->assign('comments', getcomments($page['type'], $page['typeid']));
$smarty->assign('page', $page);

// --Передаем данные шаблонизатору--
// Количество MySQL запросов
$smarty->assign('mysql', $DB->getStatistics());
// Запускаем шаблонизатор
$smarty->display('itemset.tpl');
?>