#!/bin/sh
#
# MIT License
# 
# Copyright (c) 2020 Kai Thoene
# 
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#
#----------
# Get Startup Variables
ME="$0"
MYNAME=`basename "$ME"`
MYDIR=`dirname "$ME"`
MYDIR=`cd "$MYDIR"; pwd`
WD=`pwd`
#
#----------
# Internal Script Functions
#
usage() {
  cat >&2 <<EOF
Usage: $MYNAME [OPTIONS] ARG [...]
  Important -- Put options before arguments.
EOF
}
#
#----------
# Library Script Functions
#
error() {
  echo "$MYNAME:ERROR -- $*" >&2
}

info() {
  echo "$MYNAME:INFO -- $*" >&2
}

debug() {
  echo "$MYNAME:DEBUG -- $*" >&2
}

warn() {
  echo "$MYNAME:WARNING -- $*" >&2
}

cmd_exists() {
  type "$1" 2>&1 > /dev/null
  return $?
}

check_tool() {
  while [ -n "$1" ]; do
    type "$1" > /dev/null 2>&1 || return 1
    shift
  done
  return 0
}

check_tools() {
  while [ -n "$1" ]; do
    check_tool "$1" || {
      error "Cannot find program '$1'!"
      exit 1
    }
    shift
  done
  return 0
}

getyesorno() {
  # Returns 0 for YES. Returns 1 for NO.
  # Returns 2 for abort.
  DEFAULT_ANSWER="$1"
  USER_PROMPT="$2"
  unset READ_OPTS
  echo " " | read -n 1 >/dev/null 2>&1 && READ_OPTS='-n 1'
  #--
  unset OK_FLAG
  while [ -z "$OK_FLAG" ]; do
    read -r $READ_OPTS -p "QUESTION -- $USER_PROMPT" YNANSWER
    [ $? -ne 0 ] && return 2
    if [ -z "$YNANSWER" ]; then
      YNANSWER="$DEFAULT_ANSWER"
    else
      echo
    fi
    case "$YNANSWER" in
      [yY])
        YNANSWER=Y
        return 0
        ;;
      [nN])
        YNANSWER=N
        return 1
        ;;
    esac
  done
}  # getyesorno

read_string() {
  # Usage: read_string PROMPT VARIABLE
  # Returns 0 for YES. Returns 1 for NO.
  USER_PROMPT="$1"
  VARIABLE="$2"
  #--
  unset OK_FLAG
  while [ -z "$OK_FLAG" ]; do
    read -r -p "QUESTION -- $USER_PROMPT" $VARIABLE
    [ $? -ne 0 ] && return 1
    # VALUE=`eval echo \\\${$VARIABLE}`
    # echo "$VARIABLE=$VALUE RC=$RC"
    # [ -z "$VALUE" ] && return 1
    return 0
  done
}  # read_string

open34() {
	OPEN34_TMPFILE=`mktemp -p "$MYDIR"`
	exec 3>"$OPEN34_TMPFILE"
	exec 4<"$OPEN34_TMPFILE"
	rm -f "$OPEN34_TMPFILE"
}  # open34

close34() {
	exec 3>&-
	exec 4<&-
}  # close34

do_check_cmd() {
  echo "$*"
  "$@" || {
    error "Cannot do this! CMD='$*'"
    exit 1
  }
}

do_check_cmd_no_echo() {
  "$@" || {
    error "Cannot do this! CMD='$*'"
    exit 1
  }
}

do_cmd() {
  echo "$*"
  "$@"
}

do_check_cmd_output_only_on_error() {
  echo "$*"
  open34
  "$@" >&3 2>&1
  DO_CHECK_CMD_RC=$?
	[ $DO_CHECK_CMD_RC != 0 ] && cat <&4
	close34
	[ $DO_CHECK_CMD_RC != 0 ] && {
    error "Cannot do this! CMD='$*'"
    exit $DO_CHECK_CMD_RC
  }
  return 0
}

cmdpath() {
  CMD="$*"
  case "$CMD" in
    /*)
      [ -x "$CMD" ] && FOUNDPATH="$CMD"
      ;;
    */*)
      [ -x "$CMD" ] && FOUNDPATH="$CMD"
      ;;
    *)
      IFS=:
      for DIR in $PATH; do
        if [ -x "$DIR/$CMD" ]; then
          FOUNDPATH="$DIR/$CMD"
          break
        fi
      done
      unset IFS
      ;;
  esac
  if [ -n "$FOUNDPATH" ]; then
    echo "$FOUNDPATH"
  else
    return 1
  fi
}  # cmdpath

is_glibc() {
	ldd --version 2>&1 | head -1 | grep -iE '(glibc|gnu)' > /dev/null 2>&1
} # is_glibc

unset TMPFILE
unset TMPDIR
unset OPEN34_TMPFILE
at_exit() {
  [ -n "$TMPFILE" ] && [ -f "$TMPFILE" ] && rm -f "$TMPFILE"
  [ -n "$TMPDIR" ] && [ -d "$TMPDIR" ] && rm -rf "$TMPDIR"
  [ -n "$OPEN34_TMPFILE" ] && [ -f "$OPEN34_TMPFILE" ] && rm "$OPEN34_TMPFILE"
} # at_exit

trap at_exit EXIT HUP INT QUIT TERM
#
#----------
  #if TMPDIR=`mktemp -p . -d`; then
  #  trap at_exit EXIT HUP INT QUIT TERM && \
  #  (
  #    cd "$TMPDIR"
  #    echo "DISTRIBUTION=$DISTNAME"
  #  )
  #else
  #  echo "ERROR -- Cannot create temporary directory! CURRENT-DIR=`pwd`" >&2
  #  return 1
  #fi
#----------
#
#
#----------
# Read options.
#
while getopts 'h?' OPT; do
  case "$OPT" in
    h|\?) usage; exit 0;;
    o) TARGET_USER="$OPTARG";;
    *) error "Invalid option! OPTION=\"$OPT\""; usage; exit 1;;
  esac
done
shift `echo "${OPTIND}-1" | bc`
#[ -z "$*" ] && { error "Invalid option! OPTION=$*"; usage; exit 1; }
#
#----------------------------------------------------------------------
# START
#
check_tools zip git
#
DISTDIR="recursive-shortcode"
# Change to root project directory.
cd "$MYDIR"
# Make ZIP file name.
FN="${DISTDIR}.zip"
# Check existence of distribution directory.
[ -d "$DISTDIR" ] || { error "Cannot find distribution directory! DISDIR='`pwd`/$DISTDIR'"; exit 1; }
# Create ZIP file.
( cd "$DISTDIR"; git ls-files | zip "../$FN" -@ )
#
exit 0
