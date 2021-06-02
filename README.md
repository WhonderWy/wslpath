# wslpath

This is a fork of [laurent22's wslpath](https://github.com/laurent22/wslpath) with the goal to turn it into a service for absolutely no reason at all other than why not.

Also port to Python 3 because I like it more than PHP.

wslpath - Converts between Unix and Windows-style paths in WSL.
Mounts are assumed to be `/mnt/$DRIVE_LETTER`

# Installation

Instructions will be updated eventually.
<!-- Note: PHP is required.

    wget 'https://raw.githubusercontent.com/laurent22/wslpath/master/wslpath'
    chmod 755 wslpath
    sudo mv wslpath /usr/bin
-->

# Usage

    wslpath [-m|-u|-w|-h] NAME[:line[:col]]

    Output type options:

      -w           (default) prints Windows form of NAME (C:\WINNT)
      -m           like -w, but with regular slashes (C:/WINNT)
      -u           prints Unix form of NAME (/mnt/c/winnt)

    Other options:

      -h           displays usage information

    If no output type is selected, the program will try to detect the form of
    NAME and print the opposite type (eg. will print Windows form for Unix
    path).

# Features

- Auto-detects path - converts to a Unix path if it is a Windows path and vice-versa.
- Correctly handles symlinks (Since Windows does not know about the WSL symlinks, they are resolved and the link target is returned).
- Correctly handles paths under lxss directory (this is the hidden user directory under which WSL-only files and directories are located).
- Correctly handles :line:column suffix. For example, `subl $(wslpath /mnt/d/script.js:10:2)` would open D:\script.js at line 10, column 2 in Sublime Text

# License

MIT
