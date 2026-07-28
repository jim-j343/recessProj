
# ML Service — Smart Discussion Forum

Python Flask microservice used by the main Laravel app for two things:

- **Topic classification** — predicts a category (Database, Networking, Web Development, Tools, Programming, Architecture, Testing, Assignment) from a new topic's title + content.
- **Topic recommendations** — suggests topics a user might be interested in, using collaborative filtering over their engagement history.

## Setup

1. Install Python 3.11+ from python.org (check "Add Python to PATH" during install).
2. Install dependencies:
   ```
   pip install -r requirements.txt
   ```
3. Train the classifier (takes under a second):
   ```
   python train_classifier.py
   ```
   This creates `topic_classifier.pkl`, which isn't tracked in git — you need to run this once after cloning.
4. Open `app.py` and update `DB_CONFIG` with your own local MySQL username/password.
5. Start the server:
   ```
   python app.py
   ```
   Runs on `http://127.0.0.1:5001`. It uses port 5001 instead of Flask's default 5000 because 5000 is reserved by Windows on some machines — if 5001 is also taken on yours, change the port on the last line of `app.py` and let the team know, since `TopicController.php` and `RecommendationController.php` both hardcode that port.

## Endpoints

- `POST /classify` — body `{"text": "..."}` → `{"category": "..."}`
- `GET /recommend/<user_id>` → `{"recommendations": [{"topic_id": ..., "score": ...}, ...]}`
- `GET /health` — status check

## Files

- `app.py` — Flask server with the three endpoints above
- `recommender.py` — collaborative filtering logic
- `train_classifier.py` / `training_data.py` — trains and saves the classifier
- `requirements.txt` — Python dependencies
```

And double-check `ml-service/requirements.txt` matches what you're actually running (no `psycopg2` needed, since we confirmed you're on plain MySQL):
```
scikit-learn==1.4.0
pandas==2.1.0
numpy==1.26.0
flask==3.0.0
mysql-connector-python==8.3.0
joblib==1.3.0
