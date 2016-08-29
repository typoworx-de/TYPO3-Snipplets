	public static function checkModel()
	{
		$className = self::class;

		$classTableName = str_replace('\\', '_', __CLASS__);
		$classTableName = 'tx_' . strtolower(substr($classTableName, strpos($classTableName, '_') + 1));

		$tableTca = $GLOBALS['TCA'][ $classTableName ];
		$tableTcaFields = array_keys($tableTca['columns']);
		echo '<pre>';

		/*
		die(var_dump(
			$classTableName,
			$tableTcaFields
		));
		*/

		$reflection = new \ReflectionClass($className);
		$inspectClassInstance = new $className();

		$inexistentProperties = [];
		$inExistentOptionalSysProperties = [];

		$inconsistentGetterSetter = [];
		foreach($tableTcaFields as $field)
		{
			//$tcaCaseInsensitiveKey = str_replace('_', '', $field);

			$failed = false;
			$propertyName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($field);

			// Check presence of Property
			if(!$reflection->hasProperty($propertyName))
			{
				$failed = true;

				if(strpos($field, 'sys_') === 0  || strpos($field, 't3ver_') === 0 || strpos($field, 'l10n_') === 0 || $field === 'uid' || $field === 'pid')
				{
					if(empty($inExistentOptionalSysProperties[$field]))
					{
						$inExistentOptionalSysProperties[$field] = [];
					}

					$inExistentOptionalSysProperties[$field][] = 'property';
				}
				else
				{
					if(empty($inexistentProperties[$field]))
					{
						$inexistentProperties[$field] = [];
					}

					$inexistentProperties[$field][] = 'property';
				}
			}

			// Check presence of Getter
			$getterName = 'get' . ucFirst($propertyName);
			if(!$reflection->hasMethod($getterName))
			{
				$failed = true;

				if(strpos($field, 'sys_') === 0  || strpos($field, 't3ver_') === 0 || strpos($field, 'l10n_') === 0 || $field === 'uid' || $field === 'pid')
				{
					if(empty($inExistentOptionalSysProperties[$field]))
					{
						$inExistentOptionalSysProperties[$field] = [];
					}

					$inExistentOptionalSysProperties[$field][] = 'getter';
				}
				else
				{
					if(empty($inexistentProperties[$field]))
					{
						$inexistentProperties[$field] = [];
					}

					$inexistentProperties[$field][] = 'getter';
				}
			}

			// Check presence of Setter
			$setterName = 'set' . ucFirst($propertyName);
			if(!$reflection->hasMethod($setterName))
			{
				$failed = true;

				if(strpos($field, 'sys_') === 0  || strpos($field, 't3ver_') === 0 || strpos($field, 'l10n_') === 0 || $field === 'uid' || $field === 'pid')
				{
					if(empty($inExistentOptionalSysProperties[$field]))
					{
						$inExistentOptionalSysProperties[$field] = [];
					}

					$inExistentOptionalSysProperties[$field][] = 'setter';
				}
				else
				{
					if(empty($inexistentProperties[$field]))
					{
						$inexistentProperties[$field] = [];
					}

					$inexistentProperties[$field][] = 'setter';
				}
			}

			// Check Setter/Getter & Property
			if(!$failed)
			{
				$setterParameter = $reflection->getMethod($setterName)->getParameters();
				if($setterParameter[0]->getClass() !== NULL && !empty($setterParameter[0]->getClass()->name))
				{
					$paramType = $setterParameter[0]->getClass()->name;
					printf('Checking ... \'%s\' using type \'%s\'' . "\n", $propertyName, $paramType);

					if(strpos($paramType, '\\') !== FALSE)
					{
						$paramType = '\\' . ltrim($paramType, '\\');

						if(!class_exists($paramType))
						{
							echo '[!] Failed! Unknown Type/Class!' . "\n\n";
						}

						$mockArgument = new $paramType();
					}
					else
					{
						/**
						 * ToDo... we could also need a true/false ?!
						 * Doc-based Determination of Params/Types
						 * Simulate Int/String input by simple random number
						 */
						$mockArgument = rand(0, 255);
					}


					if(empty($mockArgument))
					{
						echo '[!] Failed! Cannot determine correct Argument to pass to Setter!' . "\n\n";
					}
					else
					{
						try
						{
							call_user_func_array(array($inspectClassInstance, $setterName), array($mockArgument));
							$getArgument = call_user_func_array(array($inspectClassInstance, $getterName), array($mockArgument));

							if(!empty($mockArgument) && empty($getArgument))
							{
								echo '[!] Getter returns empty value' . "\n\n";
								$inconsistentGetterSetter[$field] = [$getterName, $setterName];
							}
							else if($mockArgument !== $getArgument)
							{
								echo '[!] Setter/Getter Value mismatch' . "\n\n";
								$inconsistentGetterSetter[$field] = [$getterName, $setterName];
							}
						}
						catch(\Exception $e)
						{
							echo '[!] Setter/Getter Exception thrown.' . "\n\n";
							$inconsistentGetterSetter[$field] = [$getterName, $setterName];
						}

						if(empty($inconsistentGetterSetter[$field]))
						{
							echo '[*] Passed.' . "\n\n";
						}
					}
				}
			}

		}


		echo "\n";
		echo "\nMandatory:\n";
		print_r($inexistentProperties);

		echo "\nGetter/Setter Methods:\n";
		print_r($inconsistentGetterSetter);

		echo "\nOptional (System Fields):\n";
		print_r($inExistentOptionalSysProperties);
		exit;
	}
