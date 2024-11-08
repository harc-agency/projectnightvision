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

