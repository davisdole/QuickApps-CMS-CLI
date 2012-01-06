<?php
class QuickappsShell extends AppShell {
    public $tasks = array(
        'System.Module',
        'System.Theme'
    );

    public function main() {
        $exit = false;

		$this->out(__d('system', 'Quickapps CMS - Shell'));
		$this->hr();
		$this->out(__d('system', '[M]odules'));
		$this->out(__d('system', '[T]hemes'));
		$this->out(__d('system', '[E]xit'));

        $task = strtoupper($this->in(__d('system', 'What would you like to do?')));
        
        switch ($task) {
            case 'M':
                $this->Module->main();
            break;

            case 'T':
                $this->Theme->main();
            break;

            case 'E':
                $exit = true;
            break;
        }

        if (!$exit) {
            $this->hr();
            $this->main();
        }
    }
}