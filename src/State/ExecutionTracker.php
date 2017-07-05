<?php
/**
 * This file is part of the daikon-cqrs/state-machine project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\StateMachine\State;

use Ds\Map;
use Ds\Vector;
use Shrink0r\SuffixTree\Builder\SuffixTreeBuilder;
use Daikon\StateMachine\StateMachineInterface;
use Daikon\StateMachine\State\StateInterface;

final class ExecutionTracker
{
    private $breadcrumbs;

    private $execution_counts;

    private $state_machine;

    public function __construct(StateMachineInterface $state_machine)
    {
        $this->state_machine = $state_machine;
        $this->breadcrumbs = new Vector;
        $this->execution_counts = new Map;
        foreach ($state_machine->getStates() as $state) {
            $this->execution_counts[$state->getName()] = 0;
        }
    }

    public function track(StateInterface $state): int
    {
        $this->breadcrumbs->push($state->getName());
        $this->execution_counts[$state->getName()]++;
        return $this->execution_counts[$state->getName()];
    }

    public function getExecutionCount(StateInterface $state): int
    {
        return $this->execution_counts[$state->getName()];
    }

    public function getBreadcrumbs(): Vector
    {
        return clone $this->breadcrumbs;
    }

    public function detectExecutionLoop(): Vector
    {
        $execution_path = implode(' ', $this->breadcrumbs->toArray());
        $loop_path = $execution_path;
        $tree_builder = new SuffixTreeBuilder;
        while (str_word_count($loop_path) > count($this->state_machine->getStates())) {
            $suffix_tree = $tree_builder->build($loop_path.'$');
            $loop_path = trim($suffix_tree->findLongestRepetition());
        }
        return new Vector(explode(' ', $loop_path));
    }
}
