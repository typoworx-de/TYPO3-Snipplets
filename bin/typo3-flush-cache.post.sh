#!/bin/bash
[[ -d "${docRelPath}" ]] || exit 1;

sudo setfacl-www-data "${docRelPath}";
exit $?;
