# Magazin online de produse apicole

Proiect de învățare: Angular (HTML, CSS), Symfony (PHP), MySQL și Git.

## Scopul primei versiuni

Clientul navighează prin categorii și produse, adaugă produse în coș, completează numele, e-mailul, telefonul și adresa, alege ramburs la livrare sau plata cu cardul și confirmă comanda. Comanda este salvată în baza de date; firma primește automat detaliile prin e-mail. Plata cu cardul va fi procesată printr-un furnizor extern, fără stocarea datelor cardului în aplicație.

## Etapele de lucru

1. Inițializarea proiectelor Angular și Symfony; pornirea locală.
2. Interfața: pagină principală, categorii, catalog și detalii produs, inițial cu date de exemplu.
3. API Symfony, entități Doctrine și MySQL pentru categorii și produse.
4. Conectarea catalogului Angular la API.
5. Coșul și formularul de finalizare; salvarea comenzilor și a produselor comandate.
6. Trimiterea e-mailurilor pentru firmă și confirmarea pentru client.
7. Plata cu cardul în mod de test, apoi gestiunea sigură a rezultatului plății.
8. Administrarea produselor, verificarea fluxului complet și documentarea proiectului pentru portofoliu.

Categorii orientative: miere, stupi, ustensile, vitamine și tratamente pentru albine. Produsele și categoriile reale vor fi introduse ulterior.

## Structură

- `frontend/` — aplicația Angular (de creat în etapa 1)
- `backend/` — API Symfony (de creat în etapa 1)
- `docs/` — note și decizii de proiect

## Git

Pentru fiecare funcție: creează un branch, implementează un pas mic, verifică-l și fă un commit cu un mesaj care spune ce ai adăugat. Nu include parole, chei API, fișiere `.env.local`, `node_modules/` sau `vendor/` în Git.

Primul pas pe calculatorul tău este să verificăm versiunile Node, npm, PHP, Composer, MySQL și Git; apoi generăm proiectele cu uneltele lor oficiale și facem primul commit. Instrucțiunile exacte le alegem în funcție de mediul tău.
