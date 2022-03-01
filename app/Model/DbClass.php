<?php

namespace App\Model;

use App\Presenters\Nette;
use Dibi;

class DbClass
{
    private $db;

//    public function __construct(\Nette\Database\Connection $database)
    public function __construct(Dibi\Connection $database)
    {
        $this->db = $database;
    }

    public function saveTeamNames($values)
    {

        $sqlSEL = "SELECT * FROM tymy";
        $this->db->connect();
        $aaa = $this->db->fetchAll($sqlSEL);
        if (!empty($aaa)) {
            foreach ($aaa as $bbb) {
                $this->db->beginTransaction();
                $this->db->query(sprintf('DELETE from tymy where id_team = %d', $bbb->id_team));
                $this->db->commit();
            }
        }
        $i = 1;
        foreach ($values as $value) {
            if (!empty($value)) {
                $sql = sprintf('INSERT INTO tymy (`id_team`, `name_team`) VALUES ( %d , "%s");', $i, $value);
                $i++;
                $sqls[] = $sql;
            }
        }
        foreach ($sqls as $sqla) {
            $this->db->connect();
            $this->db->beginTransaction();
            try {
                $this->db->query($sqla);
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
            }
        }

    }

    public function getAllFromTable(string $tableName)
    {
        $this->db->connect();
        $sql = sprintf('SELECT * from %s;',$tableName);
        return $this->db->fetchAll($sql);
    }

    public function saveGameName($value)
    {
        $this->db->connect();
        $this->db->begin();
        $res = false;
        try{
            $row = $this->db->fetch('INSERT INTO games (`name_game`) VALUES(?);', $value);
        $id = $this->db->getInsertId();
            $this->db->commit();
        }catch (\Exception $e){

            $this->db->rollBack();
        }
        $this->db->disconnect();
        return $id;
    }

    public function saveGamePoints($values, $idGame)
    {
        $isArr = [];
        foreach ($values as $key => $value){
        $tempArr = [];
            $tempArr['id_games'] = $idGame;
            $tempArr['id_team'] = (int)str_replace('gamePoints', '',$key);
            $tempArr['points'] = $value;
            $isArr[] = $tempArr;
        }
        if(!empty($values)){
        $this->db->connect();

        try{
            foreach ($isArr as $arr){
                $this->db->begin();
                $this->db->query('INSERT INTO gamespoints',$arr);
                $this->db->commit();
            }
        }catch (\Exception $e){
        dump($e->getMessage());
            $this->db->rollBack();
        }
        }
    }

    /**
     * existuje jiz takova hrav v tabulce games
     * @param string $gameName
     * @return bool
     */
    public function gameExist(string $gameName): bool
    {
        try{

        $this->db->connect();
        $this->db->begin();
        $res = $this->db->fetchAll('SELECT * from games WHERE name_game = ?', $gameName);
        if(empty($res)){
            return false;
        }else{
            return true;
        }
        }catch (\Exception $e){
            $this->flashMessage('Posralo se neco s Db.', 'error');
            return true;
        }
    }

	public function saveNewPlayer($values, $countBefore = null): bool
	{

		$saveArr = ['name' => $values->name,
					'surname' => $values->surname,
					'email' => strtolower($values->email),
					'tel' => $values->tel,
					'note' => $values->note,
					'agreement' =>$values->agreement,
					'displayName' =>$values->displayName,
					'replacement' =>($countBefore !=null)?(($countBefore > 36)? true: false):false
			];
		$this->db->connect();
		try{
				$this->db->begin();
				$this->db->query('INSERT INTO registrace',$saveArr);
				$this->db->commit();
				return true;
		}catch (\Exception $e){
			$this->db->rollBack();
			return false;
		}
	}

	/**
	 * @param string $email
	 * @return bool
	 */
	public function isEmailUse(string $email) : bool
	{
		$this->db->connect();
		$this->db->begin();
		$res = $this->db->fetchAll('SELECT * from registrace WHERE email = ?', $email);
		if($res){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @return int
	 */
	public function getPlayersCount(): int
	{
		$this->db->connect();
		$this->db->begin();
		$res = $this->db->fetchAll('SELECT count(*) pocet from registrace where deleted = 0 ');
		if($res && isset($res[0]['pocet'])){
			return $res[0]['pocet'];
		}else{
			return 0;
		}
	}

	/**
	 * @return array[]|Dibi\Row[]
	 * @throws Dibi\Exception
	 */
	public function getAllPlayers()
	{
		$res =  $this->db->fetchAll('SELECT * from registrace WHERE deleted = 0 ');
		return $res;
	}

	/**
	 * @return array[]|Dibi\Row[]
	 * @throws Dibi\Exception
	 */
	public function getAllVisiblePlayers()
	{
		$res =  $this->db->fetchAll('SELECT * from registrace WHERE deleted = 0 AND displayName = 1 ');
		return $res;
	}
}