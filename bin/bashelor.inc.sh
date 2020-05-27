#/bin/bash
true=1;
false=0;

declare -A foregroundColors;
foregroundColors=(\
  ['black']='\E[0;47m'\
  ['red']='\E[0;31m'\
  ['green']='\E[0;32m'\
  ['yellow']='\E[0;33m'\
  ['blue']='\E[0;34m'\
  ['magenta']='\E[0;35m'\
  ['cyan']='\E[0;36m'\
  ['white']='\E[0;37m'\
  ['reset']='\e[0m'\
);

declare -A vanityLevel;
vanityLevel=(\
  ['error']='\E[0;31m'\
  ['success']='\E[0;32m'\
  ['warning']='\E[0;33m'\
  ['info']='\E[0;34m'\
  ['reset']='\e[0m'\
);

# @param string message
function die()
{
  echo -e "$@";
  exit 1;
}

#
# @param string vanity (ok|warning|error)
# @param string message
function message()
{
  echo -e "${vanityLevel[${1}]}${2}${vanityLevel['reset']}";
}

#
# @param string	command to execute
function try()
{
  #$(echo $@);
  setsid $@;

  if [[ $? -ne 0 ]];
  then
    message error "Error: Failed executing\e[0m\n$@";
    return 1;
  fi

  return 0;
}
