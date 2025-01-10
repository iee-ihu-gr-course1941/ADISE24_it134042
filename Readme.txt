LINK:
Το Can't Stop είναι ένα  επιτραπέζιο παιχνίδι όπου οι παίκτες προσπαθούν να είναι οι πρώτοι που θα κερδίσουν τρεις στήλες, προχωρώντας τα πιόνια τους επάνω στις στήλες με συνδυασμούς ζαριών. Όσο μεγαλύτερο το ρίσκο τόσο μεγαλύτερη και η ανταμοιβή, αλλά υπάρχει επίσης ο κίνδυνος απώλειας προόδου αν καταλήξει σε "bust".
---------------------------------
## Initialize Player API

### Description
Η `initialize_player.php` API δέχεται ένα `username` και ελέγχει αν υπάρχει ήδη κάποιος παίκτης με αυτό το όνομα στη βάση δεδομένων. Αν δεν υπάρχει, δημιουργεί έναν νέο παίκτη και ξεκινάει μια νέα συνεδρία (session).

### Endpoint
`GET /initialize_player.php?username={username}`

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
GET /join_game.php?player_id={player_id}

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
## Roll Dice API
### Description

Η `roll.php` API εκτελεί έναν γύρο ζαριών, επιστρέφει τους έγκυρους συνδυασμούς σε ζεύγη αριθμών και ελέγχει αν υπάρχουν μηδενικά. Αν ναι, αλλάζει η σειρά του ενεργού παίκτη και μηδενίζει την διαδικασία.

### Endpoint

`GET /roll.php?game_id={game_id}`

### Parameters

- **game_id (required)**: Το αναγνωριστικό του παιχνιδιού στο οποίο ενεργείς.

### Response

Η API επιστρέφει μια JSON απάντηση με την ακόλουθη δομή:

#### Αν όλα τα ζεύγη είναι [0, 0] και αλλάζει ο ενεργός παίκτης:

```json
{
  "message": "No valid combinations. Active player switched."
}

------------------------------------------
###End Turn API
###Description

Η end_turn.php API δέχεται τον game_id, ελέγχει αν ένας από τους παίκτες έχει τελειώσει τουλάχιστον 3 στήλες και κερδίζει, και αν όχι, ενημερώνει τον ενεργό παίκτη στο παιχνίδι.

###Endpoint

GET /end_turn.php?game_id={game_id}

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
-----------------------------------
## Choose API
### Description

Η `choose.php` API λαμβάνει τις επιλογές του παίκτη, τις επεξεργάζεται και αναβαθμίζει την ενεργή πρόοδο του παίκτη για τις επιλεγμένες στήλες. Η API περιμένει την απάντηση του παίκτη με χρονικό όριο 2 λεπτών, παρακάμπτει τις τιμές `0`, και ενημερώνει τη βάση δεδομένων μόνο για έγκυρες επιλογές.

### Endpoint

`GET /ADISE24_AM134042/PHP/choose.php?game_id={game_id}&valid_combinations={valid_combinations}`

### Parameters

- **game_id (required)**: Το αναγνωριστικό του παιχνιδιού στο οποίο ενεργείς.
- **valid_combinations (required)**: Οι έγκυρες επιλογές που επέστρεψε η `roll.php`.

### Response

Η API επιστρέφει μια JSON απάντηση με την ακόλουθη δομή:

#### Όταν παρουσιάζονται οι έγκυρες επιλογές στον παίκτη:

```json
{
  "message": "Please make your choice from the valid combinations below:",
  "options": {
    "1": [/* Ζεύγος 1 */],
    "2": [/* Ζεύγος 2 */],
    "3": [/* Ζεύγος 3 */]
  }
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


