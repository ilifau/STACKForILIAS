<?php

/**
 * This file is part of the STACK Question plugin for ILIAS, an advanced STEM assessment tool.
 * This plugin is developed and maintained by SURLABS and is a port of STACK Question for Moodle,
 * originally created by Chris Sangwin.
 *
 * The STACK Question plugin for ILIAS is open-source and licensed under GPL-3.0.
 * For license details, visit https://www.gnu.org/licenses/gpl-3.0.en.html.
 *
 * To report bugs or participate in discussions, visit the Mantis system and filter by
 * the category "STACK Question" at https://mantis.ilias.de.
 *
 * More information and source code are available at:
 * https://github.com/surlabs/STACK
 *
 * If you need support, please contact the maintainer of this software at:
 * stack@surlabs.es
 *
 *********************************************************************/

declare(strict_types=1);

namespace classes\ui;

use Generator;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\UI\Component\Table;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ilassStackQuestionPlugin;

/**
 * ScoringUI
 *
 * @authors Jesús Copado Mejías, Saúl Díaz Díaz , Abraham Morales Rodríguez <stack@surlabs.es>
 */
class StackScoringTableData implements DataRetrieval
{

    private ilassStackQuestionPlugin $plugin;

    public function __construct( $prt_data, float $max_weight , float $questionPoint, ilassStackQuestionPlugin $plugin)
    {
        // TODO Tipar PRT

        $this->prt_data = $prt_data;
        $this->max_weight = $max_weight;
        $this->questionPoint = $questionPoint;
        $this->plugin = $plugin;
    }


    public function getRows(
        DataRowBuilder $row_builder,
        ?array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): Generator
    {
        $records_to_display = $this->getRecords($range, $order);

        foreach ($records_to_display as $record) {

            $row_id = (string)($record['node_id'] ?? uniqid('node_row_'));
            yield $row_builder->buildDataRow($row_id, $record);
        }
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int
    {
        return count(array($this->prt_data));
    }

    protected function getRecords(
        Range  $range,
        Order  $order,
    ): array
    {
        // Get all the node info from the PRTs
        $records = [];
        $prt_max_weight = $this->prt_data->get_value();
        // TODO Check
        $prt_max_points = ($prt_max_weight / $this->max_weight) * $this->questionPoint;

        foreach ($this->prt_data->get_nodes() as $node) {
            $records[] = [
                $this->plugin->txt("sco_node_name") => $node->nodename,
                $this->plugin->txt("sco_node_positive") => ($node->truescore * $prt_max_points),
                $this->plugin->txt("sco_node_negative") => ($node->falsescore * $prt_max_points),
            ];
        }
        return $records;
    }
}
