install-rf:
	python3 -m venv env
	env/bin/python -m pip install -r tests/robot_framework/requirements.txt
	env/bin/python -m pip install --no-deps robotframework-postgresqldb
	env/bin/rfbrowser init

test-rf: ## Run Robot Framework tests
	env/bin/python -m robot -d tests/robot_framework/results -x outputxunit.xml -v headless:true tests/robot_framework

test-rf/%: ## Run Robot Framework tests with matching tag
	env/bin/python -m robot -d tests/robot_framework/results -x outputxunit.xml -i $* -v headless:true tests/robot_framework

test-rf-head/%: ## Run Robot Framework  with browser visible, with matching tag
	env/bin/python -m robot -d tests/robot_framework/results -x outputxunit.xml -i $* -v headless:false tests/robot_framework
