<?php
App::uses('AppShell', 'Console/Command');

class ThemeTask extends AppShell {
    public $tasks = array('System.Module');

    public function main() {
        $this->out(__d('system', 'Quickapps CMS - Themes'));
        $this->hr();
		$this->out(__d('system', '[C]reate new theme'));
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

        $theme = $this->_read();

        $this->hr();

        if ($created = $this->Module->build($savePath, $theme, 'theme')) {
            $this->out(__d('system', 'Your theme has been compressed and saved in: %s', $savePath . $theme['alias'] . '.zip'));
        }    
    }

    protected function _read() {
        $yaml = array(
            'info' => array(
                'admin' => false,
                'name' => null,
                'description' => null,
                'version' => null,
                'core' => null,
                'author' => null
            ),
            'stylesheets' => array(
                'all' => array('reset.css', 'styles.css')
            ),
            'regions' => array(),
            'layout' => 'default'
        );
        $themeAlias = null;
        $yaml['info']['admin'] = strtoupper($this->in(__d('system', 'Is your theme an admin theme ?'), array('Y', 'N')));
        $yaml['info']['admin'] = ($yaml['info']['admin'] == 'Y');

        while (empty($themeAlias)) {
            $themeAlias = Inflector::camelize($this->in(__d('system', 'Alias name of the theme, in CamelCase. e.g.: "MyTestTheme" [R]')));
        }

        while (empty($yaml['info']['name'])) {
            $yaml['info']['name'] = $this->in(__d('system', 'Human readable name of the theme. e.g.: "My Test Theme" [R]'));
        }

        while (empty($yaml['info']['description'])) {
            $yaml['info']['description'] = $this->in(__d('system', 'Brief description [R]'));
        }

        $yaml['info']['version'] = $this->in(__d('system', 'Theme version. e.g.: 1.0, 2.0.1 [O]'));

        if (empty($yaml['info']['version'])) {
            unset($yaml['info']['version']);
        }

        while (empty($yaml['info']['core'])) {
            $yaml['info']['core'] = $this->in(__d('system', 'Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
        }

        while (empty($yaml['info']['description'])) {
            $yaml['info']['core'] = $this->in(__d('system', 'Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
        }

        $authorName = $this->in(__d('system', 'Author name [O]'));
        $authorEmail = $this->in(__d('system', 'Author email [O]'));
        $yaml['info']['author'] = "{$authorName} <{$authorEmail}>";

        if (empty($authorName) && empty($authorEmail)) {
            unset($yaml['info']['author']);
        }

        $addDependencies = false;
        $addDependencies = strtoupper($this->in(__d('system', 'Does your theme depends of some modules ?'), array('Y', 'N')));
        $yaml['info']['dependencies'] = array();

        if ($addDependencies == 'Y') {
            $continue = true;
            $i = 1;

            while ($continue) {
                $dependency = array('name' => null, 'version' => null);

                $this->out(__d('system', '#%s', $i));

                while (empty($dependency['name'])) {
                    $dependency['name'] = Inflector::camelize($this->in(__d('system', 'Module alias')));
                }

                $dependency['version'] = trim($this->in(__d('system', 'Module version. (Optional)')));

                while (!in_array($continue, array('Y', 'N'), true)) {
                    $continue = strtoupper($this->in(__d('system', 'Add other module dependency ?'), array('Y', 'N')));
                }

                $continue = ($continue == 'Y');
                $dependency['version'] = !empty($dependency['version']) ? " ({$dependency['version']})": "";
                $yaml['info']['dependencies'][] = "{$dependency['name']}{$dependency['version']}";
                $i++;
            }
        }

        if (empty($yaml['info']['dependencies'])) {
            unset($yaml['info']['dependencies']);
        }

        $addRegion = true;
        $this->nl();
        $this->hr();
        $this->out(__d('system', 'Adding theme regions'));
        $this->hr();

        while ($addRegion) {
            $region = $this->in(__d('system', 'Region name'));

            if (!empty($region)) {
                $yaml['regions'][strtolower(Inflector::slug($region, '-'))] = $region;
            }

            $addRegion = strtoupper($this->in(__d('system', 'Add other region'), array('Y', 'N')));
            $addRegion = ($addRegion == 'Y');
        }

        return array(
            'alias' => $themeAlias,
            'yaml' => $yaml
        );
    }    
}
