<?php
App::uses('AppShell', 'Console/Command');

class ThemeTask extends AppShell {
    public $tasks = array('System.Module');

    public function main() {
        $this->out(__d('system', 'Quickapps CMS - Themes'));
        $this->hr();
		$this->out(__d('system', '[C]reate new themes'));
		$this->out(__d('system', '[L]ist installed themes'));
		$this->out(__d('system', '[I]nfo about theme'));
		$this->out(__d('system', '[E]exit'));
        $do = strtoupper($this->in(__d('system', 'What would you like to do?')));
        $exit = false;

        switch ($do) {
            case 'C':
                $this->create();
            break;

            case 'L':
                $this->listModules();
            break;

            case 'I':
                $this->info();
            break;

            case 'E':
                $exit = true;
            break;

            default:
                $this->out(__d('system', 'You have made an invalid selection.'));
        }

        if (!$exit) {
            $this->hr();
            $this->main();
        }
    }

    public function listModules($numerate = false) {
        $this->Module->listModules($numerate, 'theme');
    }

    public function info() {
        $this->Module->info('theme');
    }

    public function create() {
        $savePath = ROOT . DS . 'webroot' . DS . 'files' . DS;

        if (!is_writable($savePath)) {
            $this->out(__d('system', 'Write permission ERROR: %s', $savePath));

            return;
        }

        $module = $this->_read();

        $this->hr();

        if ($created = $this->_build($savePath, $module)) {
            $this->out(__d('system', 'Your module has been compressed and saved in: %s', $savePath . $module['alias'] . '.zip'));
        }    
    }
}
