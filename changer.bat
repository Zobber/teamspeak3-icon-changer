@echo off

rem Author: Adams <adams@eterprime.eu> 

set FOUND=

for %%e in (%PATHEXT%) do (
  for %%X in (php%%e) do (
    if not defined FOUND (
      set FOUND=%%~$PATH:X
    )
  )
)

if not defined FOUND (
  echo "Nie odnaleziono instalacji PHP na tym komputrze."
  exit
)

%FOUND% changer.php
pause