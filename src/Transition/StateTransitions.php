<?php
/**
 * This file is part of the daikon-cqrs/state-machine project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\StateMachine\Transition;

use Countable;
use Ds\Map;
use IteratorAggregate;
use Traversable;
use Daikon\StateMachine\Error\InvalidStructure;
use Daikon\StateMachine\State\StateInterface;
use Daikon\StateMachine\State\StateMap;
use Daikon\StateMachine\State\StateSet;
use Daikon\StateMachine\Transition\TransitionInterface;
use Daikon\StateMachine\Transition\TransitionSet;

final class StateTransitions implements IteratorAggregate, Countable
{
    private $internal_map;

    public function __construct(StateMap $states, TransitionSet $transitions)
    {
        $this->internal_map = new Map;
        foreach ($transitions as $transition) {
            $from_state = $transition->getFrom();
            $to_state = $transition->getTo();
            if (!$states->has($from_state)) {
                throw new InvalidStructure('Trying to transition from unknown state: '.$from_state);
            }
            if ($states->get($from_state)->isFinal()) {
                throw new InvalidStructure('Trying to transition from final-state: '.$from_state);
            }
            if (!$states->has($to_state)) {
                throw new InvalidStructure('Trying to transition to unknown state: '.$to_state);
            }
            if ($states->get($to_state)->isInitial()) {
                throw new InvalidStructure('Trying to transition to initial-state: '.$to_state);
            }
            $state_transitions = $this->internal_map->get($transition->getFrom(), new TransitionSet);
            $this->internal_map->put($transition->getFrom(), $state_transitions->add($transition));
        }
        $initial_state = $states->findOne(function (StateInterface $state) {
            return $state->isInitial();
        });
        $reachable_states = $this->depthFirstScan($states, $initial_state, new StateSet);
        if (count($reachable_states) !== count($states)) {
            throw new InvalidStructure('Not all states are properly connected.');
        }
    }

    public function has(string $state_name): bool
    {
        return $this->internal_map->hasKey($state_name);
    }

    public function get(string $state_name): TransitionSet
    {
        return $this->internal_map->get($state_name, new TransitionSet);
    }

    public function count(): int
    {
        return $this->internal_map->count();
    }

    public function getIterator(): Traversable
    {
        return $this->internal_map->getIterator();
    }

    public function toArray()
    {
        return $this->internal_map->toArray();
    }

    private function depthFirstScan(StateMap $states, StateInterface $state, StateSet $visited_states): StateSet
    {
        if ($visited_states->contains($state)) {
            return $visited_states;
        }
        $visited_states->add($state);
        $child_states = array_map(
            function (TransitionInterface $transition) use ($states): StateInterface {
                return $states->get($transition->getTo());
            },
            $this->get($state->getName())->toArray()
        );
        foreach ($child_states as $child_state) {
            $visited_states = $this->depthFirstScan($states, $child_state, $visited_states);
        }
        return $visited_states;
    }
}
