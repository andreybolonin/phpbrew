<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class InitCommand extends \CLIFramework\Command
{
    public function brief() { return 'initialize'; }

    public function execute()
    {
        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getBuildPrefix();
        // $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        if( ! file_exists($root) )
            mkdir( $root, 0755, true );

        if( ! file_exists($buildPrefix) )
            mkdir( $buildPrefix, 0755, true );

        if( ! file_exists($buildDir) )
            mkdir( $buildDir, 0755, true );

        // write init script
        $bashScript = $root . DIRECTORY_SEPARATOR . 'bashrc';
        // $initScript = $root . DIRECTORY_SEPARATOR . 'init';
        file_put_contents( $bashScript , $this->getBashScript() );

        echo <<<EOS
Phpbrew environment is initialized, required directories are created under

    ~/.phpbrew

Paste the following line(s) to the end of your ~/.bashrc and start a
new shell, phpbrew should be up and fully functional from there:

    source ~/.phpbrew/bashrc

For further instructions, simply run `phpbrew` to see the help message.

Enjoy phpbrew at \$HOME!!
EOS;

    }

    public function getBashScript()
    {
        return <<<'EOS'
#!/bin/bash
[[ -z "$PHPBREW_ROOT" ]] && export PHPBREW_ROOT="$HOME/.phpbrew"
[[ -z "$PHPBREW_HOME" ]] && export PHPBREW_HOME="$HOME/.phpbrew"

if [[ ! -n "$PHPBREW_SKIP_INIT" ]]; then
    if [[ -f "$PHPBREW_HOME/init" ]]; then
        . "$PHPBREW_HOME/init"
    fi
fi

function __phpbrew_set_path () {
	[[ -n $(alias perl 2>/dev/null) ]] && unalias perl 2> /dev/null

    if [[ -n $PHPBREW_ROOT ]] ; then
        export PATH_WITHOUT_PHPBREW=$(perl -e 'print join ":", grep { index($_,$ENV{PHPBREW_ROOT}) } split/:/,$ENV{PATH};')
    fi

	if [[ -z "$PHPBREW_PATH" ]]
	then
		export PHPBREW_PATH="$PHPBREW_ROOT/bin"
	fi
	export PATH=$PHPBREW_PATH:$PATH_WITHOUT_PHPBREW
    # echo "PATH => $PATH"
}

function __phpbrew_reinit () {
	if [[ ! -d "$PHPBREW_HOME" ]]
	then
		mkdir -p -p "$PHPBREW_HOME"
	fi
	echo '# DO NOT EDIT THIS FILE' >| "$PHPBREW_HOME/init"
	command $BIN env $1 >> "$PHPBREW_HOME/init"
	. "$PHPBREW_HOME/init"
	__phpbrew_set_path
}

__phpbrew_set_path

function phpbrew () {
    BIN=phpbrew
    # BIN='scripts/phpbrew.php'

	local exit_status
	local short_option
	export SHELL
	if [[ `echo $1 | awk 'BEGIN{FS=""}{print $1}'` = '-' ]]
	then
		short_option=$1 
		shift
	else
		short_option="" 
	fi
	case $1 in
		(use) if [[ -z "$2" ]]
			then
				if [[ -z "$PHPBREW_PHP" ]]
				then
					echo "Currently using system php"
				else
					echo "Currently using $PHPBREW_PHP"
				fi
			else
				code=$(command $BIN env $2) 
				if [ -z "$code" ]
				then
					exit_status=1 
				else
					eval $code
					__phpbrew_set_path
				fi
			fi ;;
		(switch) 
            if [[ -z "$2" ]]
			then
				command $BIN switch
			else
				$BIN use $2
				__phpbrew_reinit $2
			fi ;;
		(off) 
            unset PHPBREW_PHP
			eval `$BIN env`
			__phpbrew_set_path
			echo "phpbrew is turned off." ;;
		(switch-off) 
            unset PHPBREW_PHP
			__phpbrew_reinit
			echo "phpbrew is switched off." ;;
		(*) command $BIN $short_option "$@"
			exit_status=$?  ;;
	esac
	hash -r
	return ${exit_status:-0}
}


EOS;

    }

}
