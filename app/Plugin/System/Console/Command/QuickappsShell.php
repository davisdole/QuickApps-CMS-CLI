<?php
App::uses('ConnectionManager', 'Model');
App::uses('Model', 'Model');

class QuickappsShell extends AppShell {
    public $tasks = array(
        'System.Module',
        'System.Theme',
        'System.Utility'
    );

    public function main() {
        $this->out(__d('system', 'Quickapps CMS - Shell'));
		$this->hr();
		$this->out(__d('system', '[M]odule Shell'));
		$this->out(__d('system', '[T]hemes Shell'));
		$this->out(__d('system', '[U]tility Shell'));
		$this->out(__d('system', '[E]xit'));
        
        $exit = false;
        $task = strtoupper($this->in(__d('system', 'What would you like to do?')));

        switch ($task) {
            case 'M':
                $this->Module->main();
            break;

            case 'T':
                $this->Theme->main();
            break;

            case 'U':
                $this->Utility->main();
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

/**
 * Prepares data in Config/Schema/data/ for the install script.
 * if no table_name is given all tables will be processed.
 *
 * Usage: ./cake system.quickapps export_data [table_name]
 */
    public function export_data() {
        $connection = 'default';
        $tables = array();

        if (!isset($this->args[0])) {
            $tables = $this->getAllTables($connection);
        } else {
            $tables[] = trim($this->args[0]);
        }

        foreach ($tables as $table) {
            $records = array();
            $modelAlias = Inflector::classify($table);
            $model = new Model(array('name' => $modelAlias, 'table' => $table, 'ds' => $connection));
            $records = $model->find('all', array('recursive' => -1));
            $recordString = '';

            foreach ($records as $record) {
                $values = array();

                foreach ($record[$modelAlias] as $field => $value) {
                    $value = str_replace("'", "\'", $value);
                    $values[] = "\t\t\t'{$field}' => '{$value}'";
                }

                $recordString .= "\t\tarray(\n";
                $recordString .= implode(",\n", $values);
                $recordString .= "\n\t\t),\n";
            }

            $content = "<?php\n";
                $content .= "class " . $modelAlias . " {\n";
                    $content .= "\tpublic \$table = '" . $table . "';\n";
                    $content .= "\tpublic \$records = array(\n";
                        $content .= $modelAlias != 'User' ? $recordString : '';
                    $content .= "\t);\n\n";
                $content .= "}\n";

            App::uses('File', 'Utility');

            $filePath = APP . 'Config' . DS . 'Schema' . DS . 'data' . DS . $modelAlias . '.php';
            $file = new File($filePath, true);

            $file->write($content);
            $this->out('File created: ' . $filePath);
        }   
    }   

/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @param string $useDbConfig Connection name to scan.
 * @return array Array of tables in the database.
 */
	public function getAllTables($useDbConfig = null) {
		if (!isset($useDbConfig)) {
			$useDbConfig = $this->connection;
		}

		$tables = array();
		$db = ConnectionManager::getDataSource($useDbConfig);
		$db->cacheSources = false;
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		if ($usePrefix) {
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}
		if (empty($tables)) {
			$this->err(__d('cake_console', 'Your database does not have any tables.'));
			$this->_stop();
		}
		return $tables;
	}
}