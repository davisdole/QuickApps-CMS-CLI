<?php
App::uses('AppShell', 'Console/Command');

class ModuleTask extends AppShell {
    public $uses = array('Module');

    public function main() {
        $this->out(__d('system', 'Quickapps CMS - Modules'));
        $this->hr();
		$this->out(__d('system', '[C]reate new module'));
		$this->out(__d('system', '[L]ist installed modules'));
		$this->out(__d('system', '[I]nfo about module'));
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

    public function listModules($numerate = false, $type = 'module') {
        $modules = $this->Module->find('all',
            array(
                'conditions' => array(
                    'Module.type' => $type
                )
            )
        );

        $i = 0;

        foreach ($modules as $module) {
            $i++;
            $prefix = $numerate ? "{$i}. " : '- ';
            $_yaml = $this->_readYaml($module['Module']['name']);
            $yaml = $type == 'theme' ? $_yaml['info'] : $_yaml;
            $version = isset($yaml['version']) ? " ({$yaml['version']})" : '';
            $coreTheme = $type == 'theme' && isCoreTheme($module['Module']['name']) ? ' [CORE]' : ' [SITE]';
            $this->out("{$prefix}{$yaml['name']}{$version}{$coreTheme}");
        }

        return $modules;
    }

    public function info($type = 'module') {
        $modules = $this->listModules(true, $type);
        $opt = $this->in(__d('system', 'Which %s ?', $type), range(1, count($modules)));
        $module = $modules[$opt-1];
        $_yaml = $this->_readYaml($module['Module']['name']);
        $yaml = $type == 'theme' ? $_yaml['info'] : $_yaml;
        $version = isset($yaml['version']) ? " v{$yaml['version']}" : '';

        $this->hr();
        $this->out("{$yaml['name']}{$version}");
        $this->hr();
        $this->out(__d('system', 'Machine Name: %s', $module['Module']['name']));
        $this->out(__d('system', 'Active: %s',
            ($module['Module']['status'] ? __d('system', 'Yes') : __d('system', 'No'))
        ));
        $this->out(__d('system', 'Description: %s', $yaml['description']));

        if (isset($yaml['category'])) {
            $this->out(__d('system', 'Category: %s', $yaml['category']));
        }

        $this->out(__d('system', 'Core: %s', $yaml['core']));

        if (isset($yaml['dependencies'])) {
            $this->out(__d('system', 'Dependencies:'));

            foreach ($yaml['dependencies'] as $d) {
                $this->out("\t- {$d}");
            }
        }

        if (isset($_yaml['regions'])) {
            $this->out(__d('system', 'Theme regions:'));
            
            foreach ($_yaml['regions'] as $alias => $name) {
                $this->out("\t- {$name} ({$alias})");
            }
        }
    }

    public function create() {
        $savePath = ROOT . DS . 'webroot' . DS . 'files' . DS;

        if (!is_writable($savePath)) {
            $this->out(__d('system', 'Write permission ERROR: %s', $savePath));

            return;
        }

        $module = $this->_read();

        $this->hr();

        if ($created = $this->build($savePath, $module)) {
            $this->out(__d('system', 'Your module has been compressed and saved in: %s', $savePath . $module['alias'] . '.zip'));
        }    
    }
    
    public function build($path, $info, $type = 'module') {
        $path = str_replace(DS . DS, DS, $path . DS);

        if (!is_writable($path)) {
            return false;
        }

        $source = dirname(dirname(dirname(__FILE__))) . DS . 'Templates' . DS . "qa_{$type}";
        $TypeName = $type == 'module' ? 'ModuleName' : 'ThemeName';
        $Folder = new Folder();

        $Folder->delete($path . $info['alias']);

        if ($this->__rcopy($source, $path . $info['alias'])) {
            $folders = $Folder->tree(realpath($path . $info['alias']), true, 'dir');

            foreach ($folders as $folder) {
                $folderName = basename($folder);

                if (strpos($folderName, $TypeName) !== false) {
                    rename($folder, dirname($folder) . DS . str_replace($TypeName, $info['alias'], $folderName));
                }            
            }

            $files = $Folder->tree(realpath($path . $info['alias']), true, 'file');

            foreach ($files as $file) {
                $fileName = basename($file);

                if ($this->__file_ext($fileName) != 'yaml') {
                    $this->__replace_file_content($file, "/{$TypeName}/", $info['alias']);
                }

                if ($fileName == "{$TypeName}.yaml") {
                    App::uses('Spyc', 'vendors');

                    $yamlContent = Spyc::YAMLDump($info['yaml']);

                    file_put_contents($file, $yamlContent);
                }

                if ($fileName == 'default.ctp' && $type = 'theme' && !empty($info['yaml']['regions'])) {
                    $body = '';

                    foreach ($info['yaml']['regions'] as $id => $name) {
                        $body .= "\n\t\t<?php if (!\$this->Layout->emptyRegion('{$id}')): ?>";
                        $body .= "\n\t\t\t<div class=\"region {$id}\">";
                        $body .= "\n\t\t\t\t<?php echo \$this->Layout->blocks('{$id}'); ?>";
                        $body .= "\n\t\t\t</div>";
                        $body .= "\n\t\t<?php endif; ?>\n";
                    }

                    $this->__replace_file_content($file, "/\<\!-- REGIONS --\>/", $body);
                }

                if (strpos($fileName, $TypeName) !== false) {
                    rename($file, dirname($file) . DS . str_replace($TypeName, $info['alias'], $fileName));
                }
            }

            App::uses('PclZip', 'vendors');

            $zip = new PclZip($path . $info['alias'] . '.zip');

            $zip->create($path . $info['alias'], PCLZIP_OPT_REMOVE_PATH, $path);
            $Folder->delete($path . $info['alias']);

            return true;
        }

        return false;
    }

    protected function _read() {
        $yaml = array(
            'name' => null,
            'description' => null,
            'category' => null,
            'version' => null,
            'core' => null,
            'author' => null,
            'dependencies' => array()
        );
        $moduleAlias = null;

        while (empty($moduleAlias)) {
            $moduleAlias = Inflector::camelize($this->in(__d('system', 'Alias name of the module, in CamelCase. e.g.: "MyTestModule" [R]')));
        }

        while (empty($yaml['name'])) {
            $yaml['name'] = $this->in(__d('system', 'Human readable name of the module. e.g.: "My Test Module" [R]'));
        }

        while (empty($yaml['description'])) {
            $yaml['description'] = $this->in(__d('system', 'Brief description [R]'));
        }

        while (empty($yaml['category'])) {
            $yaml['category'] = $this->in(__d('system', 'Category [R]'));

            if (Inflector::camelize($yaml['category']) == 'Core') {
                $yaml['category'] = null;

                $this->out(__d('system', 'Invalid category name.'));
            }
        }

        while (empty($yaml['version'])) {
            $yaml['version'] = $this->in(__d('system', 'Module version. e.g.: 1.0, 2.0.1 [R]'));
        }

        while (empty($yaml['core'])) {
            $yaml['core'] = $this->in(__d('system', 'Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
        }

        $authorName = $this->in(__d('system', 'Author name [O]'));
        $authorEmail = $this->in(__d('system', 'Author email [O]'));
        $yaml['author'] = "{$authorName} <{$authorEmail}>";

        if (empty($authorName) && empty($authorEmail)) {
            unset($yaml['author'] );
        }

        $addDependencies = false;

        while (!in_array($addDependencies, array('Y', 'N'))) {
            $addDependencies = strtoupper($this->in(__d('system', 'Does your module depends of other modules ?'), array('Y', 'N')));
        }

        $yaml['dependencies'] = array();

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
                $yaml['dependencies'][] = "{$dependency['name']}{$dependency['version']}";
                $i++;
            }
        }

        if (empty($yaml['dependencies'])) {
            unset($yaml['dependencies']);
        }

        return array(
            'alias' => $moduleAlias,
            'yaml' => $yaml
        );
    }
  
    public function _readYaml($module) {
        App::uses('Spyc', 'vendors');
        
        if (strpos($module, 'Theme') === 0) {
            $module = str_replace_once('Theme', '', $module);
            $path = App::themePath($module) . $module . '.yaml';
        } else {
            $path = CakePlugin::path($module) . $module . '.yaml';
        }

        return Spyc::YAMLLoad($path);
    }
    
    private function __file_ext($fileName){
        return strtolower(str_replace('.', '', strtolower(strrchr($fileName, '.'))));
    }

    private function __replace_file_content($file_path, $pattern, $replacement) {
        $content = file_get_contents($file_path);

        if ($content) {
            $new_content = preg_replace($pattern, $replacement, $content);

            file_put_contents($file_path, $new_content);
        }

        return false;
    }

    private function __rcopy($src, $dst) {
        $dir = opendir($src);

        @mkdir($dst);

        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . DS . $file)) {
                    $this->__rcopy($src . DS . $file, $dst . DS . $file);
                } else {
                    if (!copy($src . DS . $file, $dst . DS . $file)) {
                        return false;
                    }
                }
            }
        }

        closedir($dir);

        return true;
    }
}
