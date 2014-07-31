<?
/**
 * inc/classes/Period.php
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


class Period extends Relation {

	public $debate;
	public $preparation;
	public $voting;
	public $counting;
	public $online;
	public $secret;

	protected $boolean_fields = array("online", "secret");


	/**
	 *
	 */
	function save_approved_ballots() {

		foreach ( $_POST['approved_id'] as $key => $ballot_id ) {
			$value = !empty($_POST['approved'][$key]);
			$sql = "UPDATE ballots SET approved=".DB::bool2pg($value)." WHERE id=".intval($ballot_id);
			DB::query($sql);
		}

	}


	/**
	 * information about the current phase
	 *
	 * @return string
	 */
	public function current_period() {
		$time = time();
		if (strtotime($this->counting) <= $time) {
			return _(strtr("Counting started at %datetime%.", array(
						'%datetime%'=>date(DATETIME_FORMAT, strtotime($this->counting))
					)));
		} elseif (strtotime($this->voting) <= $time) {
			return _(strtr("Voting started at %datetime%.", array(
						'%datetime%'=>date(DATETIME_FORMAT, strtotime($this->voting))
					)));
		} elseif (strtotime($this->preparation) <= $time) {
			return _(strtr("Voting preparation started at %datetime%.", array(
						'%datetime%'=>date(DATETIME_FORMAT, strtotime($this->preparation))
					)));
		} elseif (strtotime($this->voting) <= $time) {
			return _(strtr("Debate started at %datetime%.", array(
						'%datetime%'=>date(DATETIME_FORMAT, strtotime($this->debate))
					)));
		}
		return _(strtr("Debate starts at %datetime%.", array(
					'%datetime%'=>date(DATETIME_FORMAT, strtotime($this->debate))
				)));
	}


	/**
	 *
	 * @param unknown $content
	 * @param unknown $column
	 */
	public function dbtableadmin_print_timestamp($content, array $column) {


		//  if ($object->{$column[0].'_due'}=="t") {
		//  if ($row['preparation_due']=="t") {

		?><span<?

		$timestamp = strtotime($content);

		if ($timestamp <= time()) {
			switch ($column[0]) {
			case "debate":
				if (strtotime($this->preparation) <= time()) {
					?> class="over"<?
				} else {
					?> class="current"<?
				}
				break;
			case "preparation":
				if (strtotime($this->voting) <= time()) {
					?> class="over"<?
				} else {
					?> class="current"<?
				}
				break;
			case "voting":
				if (strtotime($this->counting) <= time()) {
					?> class="over"<?
				} else {
					?> class="current"<?
				}
				break;
			case "counting":
				?> class="over"<?
				break;
			default:
				trigger_error("invalid column name".$column[0], E_USER_NOTICE);
			}
		}

		?>><?
		echo date(DATETIME_FORMAT, $timestamp);

		?></span><?


	}


	/**
	 *
	 * @param string  $colname
	 * @param mixed   $default
	 * @param integer $id
	 * @param boolean $disabled
	 * @param array   $column
	 */
	public function dbtableadmin_edit_timestamp($colname, $default, $id, $disabled, $column) {
		if ($default)  $default = date(DATETIME_FORMAT, strtotime($default));
		input_text($colname, $default, $disabled, 'size="30"');
	}


}
