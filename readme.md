## Opis ##

Plugin jest częścią zadania i stanowi uzupełnienie konkretnej strony będącej zadaniem na kursie CMS (PJATK) 

## Wymagania ##

WordPress 6.x+
Zainstalowany i aktywny plugin Forminator
Konto administratora WordPress

## przygotowanie formularza w Forminatorze ##
Upewnij się, że pola formularza mają odpowiednie Field ID, np.:

textarea-1 – dane pacjenta
radio-1 – typ pracy
checkbox-1 – materiał
date-1 – termin
upload-1 – plik STL
hidden-1 – data systemowa
hidden-2 – IP (lub pole systemowe Forminatora)


## Instalacja ## 

Pobierz plik  t-dent-panel-STL.php
W katalogu /wp-content/plugins/ umieść katalog t-dent-panel-STL/
umieść w nim plik t-dent-panel-STL.php

```
plugins/
  |_
    t-dent-panel-STL/
            |_
              t-dent-panel-STL.php
```

__Aktywuj wtyczkę w panelu Wtyczki__

## Konfiguracja ##

Po utworzeniu formularza w Forminatorze
Sprawdź numeryczne ID formularza (lista formularzy) np 61 i edytuj plik t-dent-panel-STL.php linia 24 (trzeba ustawić poprawny id formularza)

