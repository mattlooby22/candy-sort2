<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

class CandySort extends Component
{
    public $tubes;

    public $colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

    public $selectedColor = null;

    public $selectedTube = null;

    public $tubeCapacity = 4;

    public $solutionSteps = [];

    public $solveMessages = [];

    private $moves = [];

    private $visitedStates = [];

    public function mount()
    {
        $this->resetPuzzle();
    }

    public function render()
    {
        return view('livewire.candy-sort');
    }

    public function selectColor($color)
    {
        $this->selectedColor = $color;
    }

    public function handleTubeClick($tubeIndex)
    {
        if ($this->selectedColor && count($this->tubes[$tubeIndex]) < $this->tubeCapacity) {
            $this->tubes[$tubeIndex][] = $this->selectedColor;
        } elseif (! $this->selectedColor) {
            if ($this->selectedTube === $tubeIndex) {
                $this->selectedTube = null;
            } elseif ($this->selectedTube === null && count($this->tubes[$tubeIndex]) > 0) {
                $this->selectedTube = $tubeIndex;
            } elseif ($this->selectedTube !== null && $this->isValidMove($this->selectedTube, $tubeIndex)) {
                $candy = array_pop($this->tubes[$this->selectedTube]);
                $this->tubes[$tubeIndex][] = $candy;
                $this->selectedTube = null;
            }
        }
    }

    public function isValidMove($fromTube, $toTube)
    {
        if (count($this->tubes[$toTube]) >= $this->tubeCapacity) {
            return false;
        }
        if (count($this->tubes[$fromTube]) === 0) {
            return false;
        }
        if (count($this->tubes[$toTube]) === 0) {
            return true;
        }

        return end($this->tubes[$toTube]) === end($this->tubes[$fromTube]);
    }

    public function addTube()
    {
        if (count($this->tubes) < 10) {
            $this->tubes[] = [];
        }
    }

    public function removeTube()
    {
        if (count($this->tubes) > 2) {
            array_pop($this->tubes);
        }
    }

    public function resetPuzzle()
    {
        $this->tubes = array_fill(0, 5, []);
        $this->selectedTube = null;
        $this->selectedColor = null;
        $this->solutionSteps = [];
    }

    public function solvePuzzle()
    {
        $numericTubes = array_map(function ($tube) {
            return array_map(function ($color) {
                return array_search($color, $this->colors) + 1;
            }, $tube);
        }, $this->tubes);

        $this->tubes = $numericTubes;
        $this->solveMessages = [];
        $solution = $this->solve();

        if ($solution) {
            $this->solutionSteps = $solution;
        } else {
            session()->flash('error', 'No solution found!');
        }
    }

    private function solve(): ?array
    {
        Log::info('Starting solve routine', ['tubes' => $this->tubes]);
        $this->solveMessages[] = 'Starting solve routine';

        if ($this->isSolved()) {
            Log::info('Puzzle already solved');
            $this->solveMessages[] = 'Puzzle already solved';
            return $this->moves;
        }

        $stateKey = $this->getStateKey();
        if (isset($this->visitedStates[$stateKey])) {
            Log::info('Already visited state', ['state' => $stateKey]);
            return null;
        }

        $this->visitedStates[$stateKey] = true;

        for ($fromTube = 0; $fromTube < count($this->tubes); $fromTube++) {
            if (empty($this->tubes[$fromTube])) {
                continue;
            }

            for ($toTube = 0; $toTube < count($this->tubes); $toTube++) {
                if ($fromTube === $toTube) {
                    continue;
                }

                if ($this->isValidMove($fromTube, $toTube)) {
                    Log::info('Valid move found', ['from' => $fromTube, 'to' => $toTube]);
                    $this->solveMessages[] = "Valid move found from tube $fromTube to tube $toTube";

                    $candy = array_pop($this->tubes[$fromTube]);
                    $this->tubes[$toTube][] = $candy;
                    $this->moves[] = ['from' => $fromTube, 'to' => $toTube];

                    // TODO: refresh tubes
                    // $this->emitSelf('refreshTubes'); // Emit event to refresh tubes

                    $solution = $this->solve();
                    if ($solution !== null) {
                        return $solution;
                    }

                    Log::info('Backtracking', ['from' => $toTube, 'to' => $fromTube]);
                    $this->solveMessages[] = "Backtracking from tube $toTube to tube $fromTube";

                    $candy = array_pop($this->tubes[$toTube]);
                    $this->tubes[$fromTube][] = $candy;
                    array_pop($this->moves);

                    //$this->emitSelf('refreshTubes'); // Emit event to refresh tubes
                }
            }
        }

        Log::info('No solution found at this branch');
        $this->solveMessages[] = 'No solution found at this branch';
        return null;
    }

    private function getStateKey(): string
    {
        return json_encode($this->tubes);
    }

    private function isSolved(): bool
    {
        foreach ($this->tubes as $tube) {
            if (empty($tube)) {
                continue;
            }
            if (! $this->isCompleteTube(array_search($tube, $this->tubes))) {
                return false;
            }
        }

        return true;
    }

    private function isCompleteTube(int $tubeIndex): bool
    {
        $tube = $this->tubes[$tubeIndex];
        if (empty($tube)) {
            return true;
        }
        $firstCandy = $tube[0];
        foreach ($tube as $candy) {
            if ($candy !== $firstCandy) {
                return false;
            }
        }

        return true;
    }
}
