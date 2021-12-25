<?php

/**    plugin.allcps.php
 *    Checkpoint info plugin for XAseco by Spyker, 2011
 *    Configuration file: spyke_allcps.xml
 *    Copy this file to xaseco plugins folder
 *
 *    Modified by maze
 */
Aseco::registerEvent('onSync', 'undef_sync');
Aseco::registerEvent('onCheckpoint', 'checkpoint');
Aseco::registerEvent('onNewChallenge', 'spyke_ingame_record');
Aseco::registerEvent('onBeginRound', 'spyke_ingame_record');
Aseco::registerEvent('onEndRound', 'spyke_manialink_end');
Aseco::registerEvent('onStartup', 'info');

// Stolen from basic.inc.php and adjusted
function cp_formatTime ($MwTime, $hsec = true) {

	if ($MwTime == -1) {
		return '???';
	}
	else {
		$hseconds = (($MwTime - (floor($MwTime/1000) * 1000)) / 10);
		$MwTime = floor($MwTime / 1000);
		$hours = floor($MwTime / 3600);
		$MwTime = $MwTime - ($hours * 3600);
		$minutes = floor($MwTime / 60);
		$MwTime = $MwTime - ($minutes * 60);
		$seconds = floor($MwTime);
		if ($hsec) {
			if ($hours) {
				return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $seconds, $hseconds);
			}
			else {
				return sprintf('%d:%02d.%02d', $minutes, $seconds, $hseconds);
			}
		}
		else {
			if ($hours) {
				return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
			}
			else {
				return sprintf('%d:%02d', $minutes, $seconds);
			}
		}
	}
}

define('ALL_CPS_VERSION', '2');

function undef_sync($aseco)
{
    $aseco->plugin_versions[] = array(
        'plugin'   => 'plugin.spyke_allcps.php',
        'author'   => 'Spyker',
        'version'   => ALL_CPS_VERSION
    );
}


function info($aseco, $command)
{
    global $aseco, $info;

    $info = new info_class();
    $info->Aseco = $aseco;
    $info->Startup();
}


function spyke_ingame_record($aseco, $player)
{
    global $dedi_db, $info,
        $positive_cp_color, $negative_cp_color,
        $rank_color, $maxrecs, $show_time;

    if (IN_XASECO) {
        global $dedi_db;
    } else {
        $dedi_db = $this->Aseco->getPlugin('DediMania')->dedi_db;
    }

    $rank = 0;
    while ($rank <= count($dedi_db['Challenge']['Records'])) {
        $dedidata['login'] = $dedi_db['Challenge']['Records'][$rank]['Login'];
        $dediarray[$dedidata['login']] = array("checkpoints" => $dedi_db['Challenge']['Records'][$rank]['Checks']);
        $rank++;
    }

    unset($info->dedicheck);
    $info->dedicheck = $dediarray;

    $settings = simplexml_load_file('spyke_allcps.xml');
    $positive_cp_color = $settings->cpcolor->positive_cp_color;
    $negative_cp_color = $settings->cpcolor->negative_cp_color;
    $rank_color = $settings->cpcolor->rank_color;
    $show_time = $settings->show_time;
    $show_dedimania = $settings->show_dedimania;
}


function checkpoint($aseco, $command)
{
    global $aseco, $dedi_db,
        $positive_cp_color, $negative_cp_color,
        $info, $rank_color, $show_time, $maxrecs;


    unset($deditemp);
    unset($dediperso);
    unset($dedirank);

    $login = $command[1];
    $timeref = $command[2];
    $cp = $command[4];
    $show_time = 0 + $show_time;
    // $aseco->console('[plugin.spyke_allcps.php] cp '.$cp.':'.$timeref);

    $deditemp = $info->dedicheck[$login];
    $dediperso = $deditemp['checkpoints'];

    if (empty($dedi_db['Challenge']['Records'][0]['Checks'])) {
        $dedibestof = false;
    } else {
        $deditime = $dedi_db['Challenge']['Records'][0]['Checks']; //bestdedidiff
        $dedidiff = $timeref - $deditime[$cp];
        // $aseco->console('[plugin.spyke_allcps.php] dedi top '.$dedidiff.':'.implode(',', $dedi_db['Challenge']['Records'][0]['Checks']));
        if ($dedidiff <= 0) {
            $dedibestof = "-" . cp_formatTime(abs($dedidiff));
            $dedibestof = $negative_cp_color . $dedibestof;
        } else {
            $dedibestof = "+" . cp_formatTime(abs($dedidiff));
            $dedibestof = $positive_cp_color . $dedibestof;
        }
    }

    if (empty($dediperso)) {
        $persodedibest = false;
    } else {
        $dedidiff = $timeref - $dediperso[$cp]; //individualdedidiff
        // $aseco->console('[plugin.spyke_allcps.php] dedi pb '.$dedidiff.':'.implode(',', $dediperso));
        if ($dedidiff <= 0) {
            $persodedibest = "-" . cp_formatTime(abs($dedidiff));
            $persodedibest = $negative_cp_color . $persodedibest;
        } else {
            $persodedibest = "+" . cp_formatTime(abs($dedidiff));
            $persodedibest = $positive_cp_color . $persodedibest;
        }
    }

    $xmltext = '<?xml version="1.0" encoding="UTF-8"?>';
    $xmltext .= '<manialink id="' . $info->manialink_id1 . '"></manialink>';
    $aseco->client->query("SendDisplayManialinkPage", $xmltext, 0, false);
    $xmlchrono = $info->getManialinkchrono($cp, $dedibestof, $persodedibest);
    $aseco->client->query("SendDisplayManialinkPageToLogin", $login, $xmlchrono, $show_time, false);
}

function spyke_manialink_end($aseco, $command)
{
    global $info;
    $xmltext = '<?xml version="1.0" encoding="UTF-8"?>';
    $xmltext .= '<manialink id="' . $info->manialink_id1 . '"></manialink>';
    $aseco->client->query("SendDisplayManialinkPage", $xmltext, 0, false);
}

class info_class
{
    public $Aseco, $localcheck, $dedicheck, $manialink_id1, $manialink_id2;


    function Startup()
    {
        //settings from config-file
        $settings = simplexml_load_file('spyke_allcps.xml');

        $this->frame2_custom_posn_x = $settings->c_left_top2_point->hx;
        $this->frame2_custom_posn_y = $settings->c_left_top2_point->vy;
        $this->frame3_custom_posn_x = $settings->c_left_top3_point->hx;
        $this->frame3_custom_posn_y = $settings->c_left_top3_point->vy;
        $this->frame4_custom_posn_x = $settings->c_left_top4_point->hx;
        $this->frame4_custom_posn_y = $settings->c_left_top4_point->vy;

        $this->text_color = $settings->cpcolor->text_color;
        $this->rank_color = $settings->cpcolor->rank_color;
        $this->manialink_id1 = $settings->manialink_id1;
        $this->manialink_id2 = $settings->manialink_id2;
    }

    function getManialinkchrono($cp, $dedibestof, $persodedibest)
    {
        $cp = $cp + 1;
        $xmlchrono = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlchrono .= '<manialink id=' . $this->manialink_id2 . '>';

        $xmlchrono .= '<frame posn="' . $this->frame2_custom_posn_x . ' ' . $this->frame2_custom_posn_y . ' 0.3">';
        $xmlchrono .= '<quad posn="0 19.1 0.3" sizen="20 4"/>';
        $xmlchrono .= '<label scale="0.6" posn="0 1.2 0.1" halign="center" valign="center" style="TextRaceChrono" text="$s$' . $cp . '"/>';
        $xmlchrono .= '</frame>';

        if ($dedibestof) {
            $xmlchrono .= '<frame posn="' . $this->frame3_custom_posn_x . ' ' . $this->frame3_custom_posn_y . ' 0.3">';
            $xmlchrono .= '<quad posn="0 19.1 0.3" sizen="20 4"/>';
            $xmlchrono .= '<label scale="0.6" posn="0 1.2 0.1" halign="center" valign="center" style="TextRaceChrono" text="$s$' . $dedibestof . '"/>';
            $xmlchrono .= '</frame>';
        }

        if ($persodedibest) {
            $xmlchrono .= '<frame posn="' . $this->frame4_custom_posn_x . ' ' . $this->frame4_custom_posn_y . ' 0.3">';
            $xmlchrono .= '<quad posn="0 19.1 0.3" sizen="20 4"/>';
            $xmlchrono .= '<label scale="0.6" posn="0 1.2 0.1" halign="center" valign="center" style="TextRaceChrono" text="$s$' . $persodedibest . '"/>';
            $xmlchrono .= '</frame>';
        }

        $xmlchrono .= '</manialink>';
        return $xmlchrono;
    }
}
