<?php

/**
 * RecordSet is used by Database to provide a layer for handle multiple result
 * rows.
 *
 * Usage example:<code>
 * $rs = $db->query('SELECT id, name FROM tabelle', DATABASE::FETCH_ASSOC);
 * while($rs->next()) {
 *     echo $rs->getString('name') . '(' . $rs->getInteger('id') . ')<br />';
 * }
 * echo 'Total rows: ' . $rs->count();
 * </code>
 *
 * @author André Schmidt <schmidt at softwarecreator dot de>
 * @author David Molineus <david at molineus dot de>
 * @package phareon.lib.database
*/
class RecordSet extends Record
{
    /**
     * fetch mode
     *
     * @var int
    */
    protected $mode;
    
    /**
	 * constructor
	 *
	 * @return void
	 * @param resource
	 * @param int $mode fetch mode
	*/
	function __construct($result, $mode = Database::FETCH_ASSOC)
	{
		$this->result = $result;
		$this->mode = $mode;
	}
	
	/**
	 * go to next record
	 *
	 * @return bool
	*/
	public function next()
	{//Schritt für Schritt durch die Ergebnisliste
		$this->row = mysql_fetch_array($this->result, $this->mode);

		if ($this->row === false)
		{
			return false;
		}

		return true;
	}

    /**
     * reset record pointer
     *
     * @return void
    */
	public function reset()
	{//setzt Zeiger an den Anfang
		mysql_data_seek($this->result, 0);
	}
}

?>
