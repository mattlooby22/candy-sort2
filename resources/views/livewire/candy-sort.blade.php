<div>
    <div class="message-container" id="message-container">
        @if (session()->has('error'))
            <div class="message error">{{ session('error') }}</div>
        @endif
    </div>
    <div class="game-container">
        <h1 style="text-align: center">Candy Sort Puzzle</h1>
        
        <div class="color-picker">
            @foreach ($colors as $color)
                <div class="color-option" style="background-color: {{ $color }}" wire:click="selectColor('{{ $color }}')"></div>
            @endforeach
        </div>
        
        <div class="tubes-container">
            @foreach ($tubes as $index => $tube)
                <div class="tube @if ($selectedTube === $index) selected @endif" wire:click="handleTubeClick({{ $index }})">
                    @foreach ($tube as $color)
                        <div class="candy" style="background-color: {{ $color }}"></div>
                    @endforeach
                </div>
            @endforeach
        </div>
        
        <div class="controls">
            <button wire:click="addTube">Add Tube</button>
            <button wire:click="removeTube">Remove Tube</button>
            <button wire:click="resetPuzzle">Reset</button>
            <button wire:click="solvePuzzle">Solve</button>
        </div>

        <div class="solution-steps">
            @if ($solutionSteps)
                <h3>Solution Steps:</h3>
                @foreach ($solutionSteps as $index => $move)
                    <div class="step">{{ $index + 1 }}. Move from tube {{ $move['from'] + 1 }} to tube {{ $move['to'] + 1 }}</div>
                @endforeach
            @endif
        </div>
    </div>
    <div class="solve-window">
        @foreach ($solveMessages as $message)
            <div class="solve-message">{{ $message }}</div>
        @endforeach
    </div>
    
</div>
