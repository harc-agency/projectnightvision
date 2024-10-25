
(Back to [API Reference](../README.md))

### Dreams
- **id** (autoincrement)
- **overall_theme** (string): Main theme of the dream
- **possible_meaning** (text): Interpretation of dream's meaning
- **sentiment** (enum: positive, negative, neutral): Overall sentiment
- **lucidity** (boolean): Was the dream lucid?
- **recurring_dream** (boolean): Is the dream recurring?
- **resolution** (string): How the dream ended
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Symbols
- **id** (autoincrement)
- **dream_id** (foreign key)
- **symbol** (string): Notable symbol in the dream
- **interpretation** (text): Symbol's meaning
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Settings
- **id** (autoincrement)
- **dream_id** (foreign key)
- **environment** (string): Environment type (e.g., forest, city)
- **time_of_day** (string): Time during the dream (e.g., night, day)
- **weather** (string): Weather conditions in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Characters
- **id** (autoincrement)
- **dream_id** (foreign key)
- **character_role** (string): Role (e.g., protagonist, antagonist)
- **character_type** (string): Type (e.g., friend, stranger)
- **emotion** (string): Emotion associated with character
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Emotions
- **id** (autoincrement)
- **dream_id** (foreign key)
- **emotion** (string): Emotion felt (e.g., fear, joy)
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Actions
- **id** (autoincrement)
- **dream_id** (foreign key)
- **action_type** (string): Action by dreamer or other
- **action_description** (text): Description of the action
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Actionable Steps
- **id** (autoincrement)
- **dream_id** (foreign key)
- **step** (text): Suggested actionable step from analysis
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Physical Sensations
- **id** (autoincrement)
- **dream_id** (foreign key)
- **sensation** (string): Physical sensation felt in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)

### Sound and Audio Cues
- **id** (autoincrement)
- **dream_id** (foreign key)
- **sound** (string): Sound heard in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)
