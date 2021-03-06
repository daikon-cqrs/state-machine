<?php
/**
 * This file is part of the daikon-cqrs/state-machine project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\StateMachine\Tests\Builder;

use Daikon\StateMachine\Builder\YamlStateMachineBuilder;
use Daikon\StateMachine\Param\Input;
use Daikon\StateMachine\Tests\TestCase;

final class YamlStateMachineBuilderTest extends TestCase
{
    public function testBuild()
    {
        $stateMachine = (new YamlStateMachineBuilder($this->fixture('state_machine')))->build();

        $initialInput = new Input([ 'transcoding_required' => true ]);
        $initialOutput = $stateMachine->execute($initialInput);
        $currentState = $initialOutput->getCurrentState();
        $this->assertEquals('transcoding', $currentState);
        $input = Input::fromOutput($initialOutput)->withEvent('video_transcoded');
        $finalOutput = $stateMachine->execute($input, $currentState);
        $this->assertEquals('ready', $finalOutput->getCurrentState());
    }

    public function testNonStringConstraint()
    {
        (new YamlStateMachineBuilder($this->fixture('non_string_constraint')))->build();
    }

    /**
     * @expectedException Daikon\StateMachine\Exception\ConfigException
     */
    public function testNonExistantYamlFile()
    {
        new YamlStateMachineBuilder(__DIR__.'/foobar.yaml');
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\ConfigException
     */
    public function testInvalidStateMachineSchema()
    {
        (new YamlStateMachineBuilder($this->fixture('invalid_schema')))->build();
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\ConfigException
     * @expectedExceptionMessage
        Trying to provide custom state that isn't initial but marked as initial in config.
     */
    public function testInconsistentInitialState()
    {
        (new YamlStateMachineBuilder($this->fixture('inconsistent_initial')))->build();
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\ConfigException
     * @expectedExceptionMessage
        Trying to provide custom state that isn't interactive but marked as interactive in config.
     */
    public function testInconsistentInteractiveState()
    {
        (new YamlStateMachineBuilder($this->fixture('inconsistent_interactive')))->build();
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\ConfigException
     * @expectedExceptionMessage
        Trying to provide custom state that isn't final but marked as final in config.
     */
    public function testInconsistentFinalState()
    {
        (new YamlStateMachineBuilder($this->fixture('inconsistent_final')))->build();
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\MissingImplementation
     */
    public function testNonImplementedState()
    {
        (new YamlStateMachineBuilder($this->fixture('non_implemented_state')))->build();
    } // @codeCoverageIgnore

    /**
     * @expectedException Daikon\StateMachine\Exception\MissingImplementation
     */
    public function testNonImplementedTransition()
    {
        (new YamlStateMachineBuilder($this->fixture('non_implemented_transition')))->build();
    } // @codeCoverageIgnore

    private function fixture(string $name): string
    {
        return __DIR__.'/Fixture/Yaml/'.$name.'.yaml';
    }
}
