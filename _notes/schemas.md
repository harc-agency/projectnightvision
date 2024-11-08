(Back to [API Reference](../README.md))

### Users
- **id** (autoincrement)
- **username** (string): Unique username
- **email** (string): Unique email address
- **password** (string): Encrypted password
- **created_at** (timestamp)
- **location** (string): User's location from geolocation (fingerprint)

### Dreams
- **id** (autoincrement)
- **user_id** (foreign key): ID of the user who recorded the dream
- **content** (text): Content of the dream
- **is_public** (boolean): Indicates if the dream is public (default: true)
- **dream_date** (date): Date when the dream occurred (required but autofilled)
- **dream_location** (string): User's location from geolocation (fingerprint)
- **mood_before_sleep** (string): Mood before sleeping
- **mood_after_waking** (string): Mood upon waking
- **intensity** (integer): Scale of emotional intensity (e.g., 1-10)
- **sleep_quality** (integer): Rating of sleep quality (e.g., 1-10)
- **title** (string): Title of the dream
- **overall_theme** (string): Main theme of the dream
- **possible_meaning** (text): Interpretation of dream's meaning
- **sentiment** (enum: positive, negative, neutral): Overall sentiment
- **sleep_duration** (float): Duration of sleep in hours, dream date + fitbit
= **weather** (string): Weather from geolocation (date + location)


### Symbols
- **id** (autoincrement)
- **dream_id** (foreign key)
- **symbol** (string): Notable symbol in the dream
- **interpretation** (text): Symbol's meaning
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **symbol_context** (text): Context in which the symbol appeared

