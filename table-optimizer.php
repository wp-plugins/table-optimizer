<?php
/*
Plugin Name: Table Optimizer
Plugin URI: http://dogmap.jp/2010/01/01/table-optimizer/
Description: The plugin optimize Your WordPress Database Tables regularly.
Version: 0.1.0
Author: wokamoto
Author URI: http://dogmap.jp/
Text Domain: table-optimizer
Domain Path: /languages/

 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright (c) 2010 - wokamoto - wokamoto1973@gmail.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('OPTIMIZER_INTERVAL'))
	define('OPTIMIZER_INTERVAL', 24 * 60 * 7);
if (!defined('OPTIMIZER_SCHEDULE_HANDLER'))
	define('OPTIMIZER_SCHEDULE_HANDLER', 'optimize_table');

class OptimizeTable {

	function OptimizeTable(){
		$this->__construct();
	}
	public function __construct() {
		if ( !$this->schedule_enabled() )
			$this->optimize_table();
	}

	function optimize_table(){
		global $wpdb;

		$this->schedule_single_event();

		$tables = $wpdb->get_col('SHOW TABLES');
		$pattern = '/^'. preg_quote($wpdb->prefix) . '/i';
		foreach ( $tables as $table ) {
			if ( preg_match( $pattern, $table ) ) {
				$wpdb->query("OPTIMIZE TABLE $table");
			}
		}
	}

	// plugin deactivation
	function deactivation(){
		wp_clear_scheduled_hook(OPTIMIZER_SCHEDULE_HANDLER);
	}

	function schedule_single_event($schedule_procname = OPTIMIZER_SCHEDULE_HANDLER, $time_interval = OPTIMIZER_INTERVAL) {
		return (wp_schedule_single_event(time() + $time_interval * 60, $schedule_procname));
	}

	function schedule_enabled($schedule_procname = OPTIMIZER_SCHEDULE_HANDLER) {
		$schedule = $this->_get_schedule($schedule_procname);
		return ($schedule['enabled']);
	}

	function _get_cron_array() {
		if ( function_exists('_get_cron_array') ) {
			return _get_cron_array();
		} else {
			$cron = get_option('cron');
			return ( is_array($cron) ? $cron : false );
		}
	}

	// get wp-cron schedule
	function _get_schedule($schedule_procname = OPTIMIZER_SCHEDULE_HANDLER) {
		$schedule = array(
			'procname' => '' ,
			'enabled' => FALSE ,
			'time' => '' ,
		);

		$crons = $this->_get_cron_array();
		if ( !empty($crons) ) {
			foreach ( $crons as $time => $tasks ) {
				foreach ( $tasks as $procname => $task ) {
					if ($procname === $schedule_procname) {
						$schedule['procname'] = $procname;
						$schedule['time'] = $time;
						$schedule['enabled'] = true;
						break;
					}
				}
				if ($schedule['enabled']) break;
			}
			unset($procname); unset($task);
			unset($time); unset ($tasks);
		}
		unset($crons);

		return ($schedule);
	}
}

$optimizer = new OptimizeTable();

add_action(OPTIMIZER_SCHEDULE_HANDLER, array(&$optimizer, 'optimize_table'));

if ( function_exists('register_deactivation_hook') )
	register_deactivation_hook(__FILE__, array(&$optimizer, 'deactivation'));

unset($optimizer);
?>