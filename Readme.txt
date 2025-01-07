## Initialize Player API

### Description
Η `initialize_player.php` API δέχεται ένα `username` και ελέγχει αν υπάρχει ήδη κάποιος παίκτης με αυτό το όνομα στη βάση δεδομένων. Αν δεν υπάρχει, δημιουργεί έναν νέο παίκτη και ξεκινάει μια νέα συνεδρία (session).

### Endpoint
`GET /ADISE24_AM134042/PHP/initialize_player.php?username={username}`

### Parameters
- `username` (required): Το όνομα του χρήστη που θέλεις να αρχικοποιήσεις.

### Response
Η API επιστρέφει μια JSON απάντηση με την ακόλουθη δομή:

- Αν το `username` υπάρχει ήδη:
  ```json
  {
    "message": "Username already exists"
  }
--------------------------------------------
###Join Game API
###Description

Η join_game.php API δέχεται τον player_id και τον προσθέτει σε ένα ανοιχτό παιχνίδι, δημιουργώντας ένα νέο παιχνίδι αν δεν υπάρχει διαθέσιμο.

###Endpoint
GET /ADISE24_AM134042/PHP/join_game.php?player_id={player_id}

###Parameters
player_id (required): Το αναγνωριστικό του παίκτη που θέλει να συμμετάσχει στο παιχνίδι.

##Response
Το API επιστρέφει μια JSON απάντηση με την ακόλουθη δομή:
Αν ο παίκτης έγινε δεκτός ως Player1:

json
{
  "message": "Player1 joined game",
  "game_id": {game_id}
}
Αν ο παίκτης έγινε δεκτός ως Player2 και το παιχνίδι είναι τώρα ενεργό:

json
{
  "message": "Player2 joined game, game is now active",
  "game_id": {game_id}
}
Αν ο παίκτης δεν μπορεί να συμμετάσχει επειδή το παιχνίδι είναι γεμάτο:

json
{
  "message": "Error: Game already has two players"
}
Αν δεν υπάρχει διαθέσιμο παιχνίδι και δημιουργήθηκε νέο:

json
{
  "message": "Player joined new game and board initialized",
  "game_id": {game_id}
}
------------------------------------------
###End Turn API
###Description

Η end_turn.php API δέχεται τον game_id, ελέγχει αν ένας από τους παίκτες έχει τελειώσει τουλάχιστον 3 στήλες και κερδίζει, και αν όχι, ενημερώνει τον ενεργό παίκτη στο παιχνίδι.

###Endpoint

GET /ADISE24_AM134042/PHP/end_turn.php?game_id={game_id}

###Parameters

game_id (required): Το αναγνωριστικό του παιχνιδιού που θέλεις να ελέγξεις.

Response
Η API επιστρέφει μια JSON απάντηση με την ακόλουθη δομή:

Αν ο παίκτης 1 κερδίσει:

json
{
  "message": "Player1 won!",
  "winner": "Player1"
}
Αν ο παίκτης 2 κερδίσει:

json
{
  "message": "Player2 won!",
  "winner": "Player2"
}
Αν κανείς δεν κερδίσει και αλλάζει ο ενεργός παίκτης:

json
{
  "message": "Turn completed. Active player switched",
  "active_player": "player2_id" (or "player1_id" depending on switch)
}
Αν δεν βρέθηκε το παιχνίδι:

json
{
  "message": "Game not found"
}
------------------------------------
SQL Functions

###CheckPlayerProgress

###Description

Η CheckPlayerProgress function δέχεται το player_id και το game_id και επιστρέφει τον αριθμό των στηλών που έχει ολοκληρώσει ο παίκτης στο συγκεκριμένο παιχνίδι.
-----------------------------------
## Initialize New Game Board

### Description
Η `InitializePlayer` function δέχεται ένα `username` και δημιουργεί έναν νέο πίνακα παιχνιδιού αν δεν υπάρχει ήδη με αυτό το όνομα στη βάση δεδομένων.


