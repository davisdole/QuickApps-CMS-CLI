<?php
App::uses('AppShell', 'Console/Command');

class UtilityTask extends AppShell {
    public function main() {
        $this->out(__d('system', 'Quickapps CMS - Utilities'));
        $this->hr();
		$this->out(__d('system', '[C]lear cache'));
		$this->out(__d('system', '[E]xit'));
        $do = strtoupper($this->in(__d('system', 'What would you like to do?')));
        $exit = false;

        switch ($do) {
            case 'C':
                $this->clearCache();
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

    public function clearCache() {
        $paths = array(
            ROOT . DS . 'tmp' . DS . 'cache' . DS,
            ROOT . DS . 'tmp' . DS . 'cache' . DS . 'models' . DS,
            ROOT . DS . 'tmp' . DS . 'cache' . DS . 'persistent' . DS
        );

        foreach ($paths as $path) {
            $folder = new Folder($path);
            $contents = $folder->read();
            $files = $contents[1];

            foreach ($files as $file) {
                $this->out($path . $file);
                @unlink($path . $file);
            }
        }    
    }
}
