(Back to [API Reference](../README.md))

### Overview
The following schemas define the structure of the Dream Analysis application. They outline how dreams and their various components are stored and managed. Using these schemas, users can record detailed information about their dreams, including themes, symbols, settings, characters, emotions, actions, and other elements. This allows for comprehensive tracking and analysis of dream patterns, facilitating insights into subconscious thoughts and experiences.

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
- **dream_date** (date): Date when the dream occurred
- **dream_description** (text): Detailed description of the dream
- **sleep_duration** (float): Duration of sleep in hours
- **mood_before_sleep** (string): Mood before sleeping
- **mood_after_waking** (string): Mood upon waking
- **vividness** (integer): Scale of dream vividness (e.g., 1-10)
- **intensity** (integer): Scale of emotional intensity (e.g., 1-10)
- **sleep_quality** (integer): Rating of sleep quality (e.g., 1-10)
- **associated_events** (text): Waking life events that may have influenced the dream

### Symbols
- **id** (autoincrement)
- **dream_id** (foreign key)
- **symbol** (string): Notable symbol in the dream
- **interpretation** (text): Symbol's meaning
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **symbol_context** (text): Context in which the symbol appeared

### Settings
- **id** (autoincrement)
- **dream_id** (foreign key)
- **environment** (string): Environment type (e.g., forest, city)
- **time_of_day** (string): Time during the dream (e.g., night, day)
- **weather** (string): Weather conditions in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **location_specifics** (text): Specific details about the location
- **soundscape** (text): Sounds present in the setting

### Characters
- **id** (autoincrement)
- **dream_id** (foreign key)
- **character_role** (string): Role (e.g., protagonist, antagonist)
- **character_type** (string): Type (e.g., friend, stranger)
- **emotion** (string): Emotion associated with character
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **character_name** (string): Name of the character
- **relationship_to_dreamer** (string): Relationship to the dreamer
- **character_description** (text): Description of the character

### Emotions
- **id** (autoincrement)
- **dream_id** (foreign key)
- **emotion** (string): Emotion felt (e.g., fear, joy)
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **intensity** (integer): Intensity of the emotion
- **emotion_context** (text): Context in which the emotion was felt

### Actions
- **id** (autoincrement)
- **dream_id** (foreign key)
- **action_type** (string): Action by dreamer or other
- **action_description** (text): Description of the action
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **consequence** (text): Consequences of the action

### Actionable Steps
- **id** (autoincrement)
- **dream_id** (foreign key)
- **step** (text): Suggested actionable step from analysis
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **priority** (integer): Priority level of the step

### Physical Sensations
- **id** (autoincrement)
- **dream_id** (foreign key)
- **sensation** (string): Physical sensation felt in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **intensity** (integer): Intensity of the sensation
- **body_part** (string): Body part where the sensation was felt

### Sound and Audio Cues
- **id** (autoincrement)
- **dream_id** (foreign key)
- **sound** (string): Sound heard in the dream
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **sound_source** (string): Source of the sound
- **description** (text): Detailed description of the sound
