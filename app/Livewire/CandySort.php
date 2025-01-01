<?php

namespace App\Livewire;

use Livewire\Component;

class CandySort extends Component
{
    public $tubes;
    public $colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];
    public $selectedColor = null;
    public $selectedTube = null;
    public $tubeCapacity = 4;
    public $solutionSteps = [];

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
        } else if (!$this->selectedColor) {
            if ($this->selectedTube === $tubeIndex) {
                $this->selectedTube = null;
            } else if ($this->selectedTube === null && count($this->tubes[$tubeIndex]) > 0) {
                $this->selectedTube = $tubeIndex;
            } else if ($this->selectedTube !== null && $this->isValidMove($this->selectedTube, $tubeIndex)) {
                $candy = array_pop($this->tubes[$this->selectedTube]);
                $this->tubes[$tubeIndex][] = $candy;
                $this->selectedTube = null;
            }
        }
    }

    public function isValidMove($fromTube, $toTube)
    {
        if (count($this->tubes[$toTube]) >= $this->tubeCapacity) return false;
        if (count($this->tubes[$fromTube]) === 0) return false;
        if (count($this->tubes[$toTube]) === 0) return true;
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

        $solver = new CandySortSolver($numericTubes, $this->tubeCapacity);
        $solution = $solver->solve();

        if ($solution) {
            $this->solutionSteps = $solution;
        } else {
            session()->flash('error', 'No solution found!');
        }
    }
}

class CandySortSolver
{
    private $tubes;
    private $tubeCapacity;
    private $moves = [];

    public function __construct(array $initialTubes, int $tubeCapacity = 4)
    {
        $this->tubes = $initialTubes;
        $this->tubeCapacity = $tubeCapacity;
    }

    public function solve(): ?array
    {
        if ($this->isSolved()) {
            return $this->moves;
        }

        for ($fromTube = 0; $fromTube < count($this->tubes); $fromTube++) {
            if (empty($this->tubes[$fromTube])) {
                continue;
            }

            for ($toTube = 0; $toTube < count($this->tubes); $toTube++) {
                if ($fromTube === $toTube) {
                    continue;
                }

                if ($this->isValidMove($fromTube, $toTube)) {
                    $candy = array_pop($this->tubes[$fromTube]);
                    $this->tubes[$toTube][] = $candy;
                    $this->moves[] = ["from" => $fromTube, "to" => $toTube];

                    $solution = $this->solve();
                    if ($solution !== null) {
                        return $solution;
                    }

                    $candy = array_pop($this->tubes[$toTube]);
                    $this->tubes[$fromTube][] = $candy;
                    array_pop($this->moves);
                }
            }
        }

        return null;
    }

    private function isValidMove(int $fromTube, int $toTube): bool
    {
        if (empty($this->tubes[$fromTube])) {
            return false;
        }

        if (count($this->tubes[$toTube]) >= $this->tubeCapacity) {
            return false;
        }

        $candy = end($this->tubes[$fromTube]);

        if (empty($this->tubes[$toTube])) {
            if ($this->isCompleteTube($fromTube)) {
                return false;
            }
            return true;
        }

        return end($this->tubes[$toTube]) === $candy;
    }

    private function isSolved(): bool
    {
        foreach ($this->tubes as $tube) {
            if (empty($tube)) {
                continue;
            }
            if (!$this->isCompleteTube(array_search($tube, $this->tubes))) {
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
?>
