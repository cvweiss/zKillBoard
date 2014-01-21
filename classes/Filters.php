<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Filters
{
	private static function grabParameters($parameters, $name)
	{
		$retValue = isset($parameters[$name]) ? $parameters[$name] : null;
		if ($retValue === null) return $retValue;
		if (!is_array($retValue)) $retValue = array($retValue);
		return $retValue;
	}

	/**
	 * @param string $table
	 */
	private static function buildWhere(&$tables, &$whereClauses, $table, $column, $parameters)
	{
		$array = self::grabParameters($parameters, $column);
		if ($array === null || !is_array($array) || sizeof($array) == 0) return "";
		// Ensure SQL safe parameters
		$cleanArray = array();
		foreach ($array as $value) $cleanArray[] = "'" . (int)$value . "'";
		$tables[] = $table;
		$not = "";
		if (Util::startsWith($column, "!")) {
			$not = " not ";
			$column = substr($column, 1);
		}
		if ($column == "groupID") {
			//$whereClauses[] = "(p.$column $not in (" . implode(",", $cleanArray) . ") or p.vGroupID $not in (" . implode(",", $cleanArray) . "))";
			$whereClauses[] = "(p.vGroupID $not in (" . implode(",", $cleanArray) . "))";
		} else $whereClauses[] = "p.$column $not in (" . implode(",", $cleanArray) . ")";
	}

	public static function buildFilters(&$tables, &$combined, &$whereClauses, &$parameters, $allTime = true)
	{
		$year = date("Y");
		$month = date("m");
		$week = date("W");
		// zz_participants filters
		$participants = "zz_participants p";
		$filterColumns = array("allianceID", "characterID", "corporationID", "factionID", "shipTypeID", "groupID", "solarSystemID", "regionID");
		foreach ($filterColumns as $filterColumn) {
			self::buildWhere($tables, $combined, $participants, $filterColumn, $parameters);
			self::buildWhere($tables, $combined, $participants, "!$filterColumn", $parameters);
		}

		if (array_key_exists("year", $parameters)) $year = (int)$parameters["year"]; // Optional
		if (array_key_exists("week", $parameters)) $week = (int)$parameters["week"]; // Optional
		if (array_key_exists("month", $parameters)) $month = (int)$parameters["month"]; // Optional
		if (!array_key_exists("pastSeconds", $parameters) && $allTime == false && (!isset($year) || !isset($week))) {
			$year = array_key_exists("year", $parameters) ? (int)$parameters["year"] : date("Y");
			$week = array_key_exists("week", $parameters) ? (int)$parameters["week"] : date("W");
		}

		if (array_key_exists("api-only", $parameters)) {
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.killID > 0";
		}

		if (array_key_exists("solo", $parameters) && $parameters["solo"] === true) {
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.number_involved = 1";
			$whereClauses[] = "p.vGroupID not in (237, 29, 31)";
		}

		if (array_key_exists("relatedTime", $parameters)) {
			$relatedTime = $parameters["relatedTime"];
			$unixTime = strtotime($relatedTime);
			if ($unixTime % 3600 != 0) throw new Exception("User attempted an unsupported value.  Fail.");
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.dttm >= '" . date("Y:m:d H:i:00", $unixTime - 3600) . "'";
			$whereClauses[] = "p.dttm <= '" . date("Y:m:d H:i:00", $unixTime + 3600) . "'";
			$parameters["limit"] = 10000;
		}
		if (array_key_exists("startTime", $parameters)) {
			$time = $parameters["startTime"];
			$unixTime = strtotime($time);
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.dttm >= '" . date("Y-m-d H:i:s", (int)$unixTime) . "'";
		}
		if (array_key_exists("endTime", $parameters)) {
			$time = $parameters["endTime"];
			$unixTime = strtotime($time);
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.dttm <= '" . date("Y-m-d H:i:s", (int)$unixTime) . "'";
		}

		if (array_key_exists("pastSeconds", $parameters)) {
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.dttm >= date_sub(now(), interval " . ((int) $parameters["pastSeconds"]) . " second)";
		}

		if (array_key_exists("iskValue", $parameters)) {
			$tables[] = "zz_participants p";
			$whereClauses[] = "p.total_price >= '" . ((int)$parameters["iskValue"]) . "'";
		}

		if (array_key_exists("w-space", $parameters)) {
			$tables[] = "zz_participants p";
			$whereClauses[] = "(regionID >= '11000001' and regionID <= '11000030')";
		}

		if (array_key_exists("beforeKillID", $parameters)) {
			$killID = (int)$parameters["beforeKillID"];
			$tables[] = "zz_participants p";
			$whereClauses[] = "killID < $killID";
			// Using this crazy subquery allows us to limit the query to certain partitions
			$whereClauses[] = "p.dttm <= (select dttm from zz_participants where killID = $killID order by killID limit 1)";
		}
		if (array_key_exists("afterKillID", $parameters)) {
			$killID = (int)$parameters["afterKillID"];
			$tables[] = "zz_participants p";
			$whereClauses[] = "killID > $killID";
			// Using this crazy subquery allows us to limit the query to certain partitions
			$whereClauses[] = "p.dttm >= (select dttm from zz_participants where killID = $killID order by killID limit 1)";
		}

		$kills = array_key_exists("kills", $parameters);
		$losses = array_key_exists("losses", $parameters); //|| (array_key_exists("solo", $parameters));
		if ((array_key_exists("mixed", $parameters) && $parameters["mixed"] == true) || array_key_exists("iskValue", $parameters)) {
		}
		else if ($losses) {
			$tables[] = $participants;
			$whereClauses[] = "p.isVictim = '1'";
		}
		else if ($kills) {
			$tables[] = $participants;
			$whereClauses[] = "p.isVictim = '0'";
		}

		$tables = array_unique($tables);
		if (sizeof($tables) == 0) $tables[] = "zz_participants p";
		foreach ($tables as $table) {
			$tablePrefix = substr($table, strlen($table) - 1, 1);
			if (isset($year)) {
				$whereClauses[] = "{$tablePrefix}.dttm >= '$year-01-01 00:00:00'";
				$whereClauses[] = "{$tablePrefix}.dttm <= '$year-12-31 23:59:59'";
			}
			if (isset($week)) {
				if (!isset($year)) throw new Exception("Must include a year when setting week!");
				$weekStart = date("Y-m-d H:i:00", strtotime("{$year}W{$week}"));
				$whereClauses[] = "{$tablePrefix}.dttm >= '$weekStart'";
				$whereClauses[] = "{$tablePrefix}.dttm <= date_add('$weekStart', interval 7 day)";
			}
			if (isset($month)) {
				if (!isset($year)) throw new Exception("Must include a year when setting month!");
				$whereClauses[] = "{$tablePrefix}.dttm >= '$year-$month-01 00:00:00'";
				$whereClauses[] = "{$tablePrefix}.dttm <= '$year-$month-31 23:59:59'";
			}
		}
	}
}
