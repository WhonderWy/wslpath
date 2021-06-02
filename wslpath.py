#! /usr/bin/env python3

from os import getcwd
from os import chdir
from os.path import basename
from sys import argv
import re

def dirname(path: str) -> str:
    return basename(path)

def usage() -> str:
    return '''wslpath [-m|-u|-w|-h] NAME[:line[:col]]

    Output type options:

    \t-w\t\t(default) prints Windows form of NAME (C:\WINNT)
    \t-m\t\tlike -w, but with regular slashes (C:/WINNT)
    \t-u\t\tprints Unix form of NAME (/mnt/c/winnt)

    Other options:

    \t-h\t\tdisplays usage information

    If no output type is selected, the program will try to detect the form of
    NAME and print the opposite type (eg. will print Windows form for Unix
    path).'''


lxssPath_ = "/mnt/c"
def lxssPath(path: str =None) -> str:
    global lxssPath_
    if path:
        lxssPath_ = path
        # lxssPath_ = trim(exec('cmd.exe /c "echo %LOCALAPPDATA%" 2> /dev/null')) . "\\lxss"
    return lxssPath_


def pathType(path) -> str:
    r = re.match('/^([A-Za-z]):(.*)$/', path)
    if r:
        return "win"
    if path[0] == "/" or path[0] == "~":
        return "unix"
    if "\\" in path:
        return "win"
    if "/" in path:
        return "unix"
    return "unix"


def slashJoin(part1: str, part2: str, slash: str) -> str:
    part1 = part1.rstrip()
    part1 = part1.removesuffix("/\\")
    part2 = part2.lstrip()
    part2 = part2.removeprefix("/\\")
    return part1 + slash + part2


def setLastSlash(path: str, addIt: bool, slash: str) -> str:
    path = path.rstrip().removesuffix("/\\")
    if (not addIt):
        return path
    return path + slash


def toSlash(path: str, slash: str) -> str:
    path = path.replace("\\", slash)
    path = path.replace("/", slash)
    return path


def explodeLineColNumbers(path: str) -> dict:
    r = re.match('/(.*):(\d+):(\d+)/', path)
    if r.end() >= 4:
        return {
            'path': r[1],
            'line': r[2],
            'col': r[3],
        }
    

    r = re.match('/(.*):(\d+)/', path)
    if r.end() >= 3:
        return {
            'path': r[1],
            'line': r[2],
        }
    

    return {
            'path': r[1],
        }


def pathToWin(inputPath: str) -> str:
    path = slashJoin(getcwd(), inputPath, '/') if inputPath[0] != '/' else inputPath
    if (not path):
        return toSlash(inputPath, "\\")

    if path.startswith("/mnt/"):
        initialDir = getcwd()

        inputDir = dirname(path)

        try:
            chdir(inputDir)
        except:
            return toSlash(inputPath, "\\")

        # output = trim(exec('cmd.exe /c cd 2> /dev/null', output))
        output = slashJoin(getcwd(), basename(path), "\\")
    else:
        output = slashJoin(lxssPath(), path, "\\")
    

    c = inputPath[len(inputPath) - 1]
    output = setLastSlash(output, c == '/' or c == "\\", "\\")

    return toSlash(output, "\\")


def pathToUnix(winPath: str) -> str:
    r = re.match('/([A-Za-z]):(.*)/', winPath)
    
    if r.end() < 3:
        output = slashJoin(getcwd(), winPath, '/')
    else:
        drive = '/mnt/' + r[1].lower()
        output = slashJoin(drive, r[2], '/')
    

    c = winPath[len(winPath) - 1]
    output = setLastSlash(output, c == '/' or c == "\\", "\\")

    return toSlash(output, '/')


def main(args):
    args = args[1:]

    operation = None
    inputPath = None
    mixedMode = False

    for i, arg in enumerate(args):

        try:
            if arg == '-h':
                print(usage())
                return

            if arg == '-w':
                operation = 'to_win'
                continue
            

            if arg == '-u':
                operation = 'to_unix'
                continue
            

            if arg == '-m':
                mixedMode = True
                operation = 'to_win'
                continue
            

            if "-" in arg and arg.find("-") != i:
                inputPath = arg.strip()
                break
        except:
            raise Exception('Unknown option: ' + arg)
    

    if not inputPath:
        raise Exception('No path provided')

    if not operation:
        operation = 'to_unix' if pathType(inputPath) == 'win' else 'to_win'
    

    pathInfo = explodeLineColNumbers(inputPath)
    outputPath = ''

    if operation == 'to_win':
        outputPath = pathToWin(pathInfo['path'])
        if mixedMode:
            outputPath = toSlash(outputPath, '/')
    

    if operation == 'to_unix':
        outputPath = pathToUnix(pathInfo['path'])
    

    if pathInfo['line']:
        outputPath += ':' + pathInfo['line']
    if pathInfo['col']:
        outputPath += ':' + pathInfo['col']

    print(outputPath)


if __name__ == "__main":
    try:
        main(argv)
    except Exception as e:
        print(str(e.args) + "\n\n" + usage() + "\n")
        exit()
