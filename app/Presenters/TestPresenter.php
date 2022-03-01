<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Dibi;


final class TestPresenter extends Nette\Application\UI\Presenter
{

    private $database;

    // pro práci s vrstvou Database Explorer si předáme Nette\Database\Explorer
    public function __construct(Dibi\Connection $database)
    {
        $this->database = $database;
    }

    public function renderDefault(){
        $this->template->prase = 'prasess';
        $teamsPoints = null;
        try{
            $this->database->connect();
            $this->database->begin();

            $values = $this->database->query('SELECT distinct (nt.name_team), gps.points, ga.name_game FROM `gamespoints` gps JOIN tymy nt on nt.id_team = gps.id_team JOIN games ga on ga.id_games = gps.id_games' );
            $teamsPoints = $values->fetchAssoc('name_team|name_game|points');

        }catch (\Exception $e){
            dump($e->getMessage());

        }
        $gameNames = [];
        $teampoints = [];
        if($teamsPoints){
            foreach ($teamsPoints as $key => $teamsPoint){
                foreach ($teamsPoint as $gamekey => $points){
                    if(!in_array($gamekey, $gameNames)){
                        $gameNames[] = $gamekey;
                    }
                    foreach ($points as $gnkey => $gn){
                                $celkem = 0;
                                if($key == $gn['name_team']){
                                    $teampoints[$key][] = $gnkey;
                                }

                    }
                }
            }
        }
        $teams = [];
        $pointsResults = [];
        foreach( $teampoints as $keya => $value){
        $teams[] = $keya;
        $tempAll = 0;
            foreach ($value as $val){
                $tempAll += $val;
            }
            $pointsResults[] = $tempAll;
        }
        $teamresults = [];
        for($i = 0; $i < (count($teams)); $i++){
        $aaa =[];
//            if($i == 0){
                $aaa[0] = $teams[$i];
                foreach($teampoints as $keyteam => $teampoint){
                    if($keyteam == $teams[$i]){
//                        $aaa[0]
                        foreach ($teampoint as $points){
                            $aaa[] = $points;
                        }
                    }
                }
                $aaa[count($teams)-1] = $pointsResults[$i];
                $teamresults[] = $aaa;
            }
        $this->template->gameNames = $gameNames;
        $this->template->teamPoints = $teampoints;
        $this->template->teams = $teams;
        $this->template->pointsResults = $pointsResults;
        $this->template->teamResults = $teamresults;

    }



}
