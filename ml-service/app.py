# app.py — The main Flask API server
from flask import Flask, request, jsonify
import joblib
import mysql.connector
from recommender import get_recommendations

app = Flask(__name__)

# Load the trained classifier once when server starts
classifier = joblib.load('topic_classifier.pkl')

# MySQL connection config
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'database': 'smartforum',
    'user': 'root',
    'password': 'password',
}


def get_engagements():
    """Fetch all engagement records from MySQL"""
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        "SELECT user_id, topic_id, engagement_type FROM user_engagements"
    )
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


# ---- ENDPOINT 1: Classify a topic ----
@app.route('/classify', methods=['POST'])
def classify_topic():
    """
    POST /classify
    Body: { "text": "how do I use foreign keys in MySQL" }
    Returns: { "category": "Technology" }
    """
    data = request.get_json()
    if not data or 'text' not in data:
        return jsonify({'error': 'text field required'}), 400
    text = data['text']
    category = classifier.predict([text])[0]
    return jsonify({'category': category})


# ---- ENDPOINT 2: Get recommendations for a user ----
@app.route('/recommend/<int:user_id>', methods=['GET'])
def recommend_topics(user_id):
    """
    GET /recommend/1
    Returns: { "recommendations": [ {topic_id: 13, score: 2.12}, ... ] }
    """
    engagements = get_engagements()
    recs = get_recommendations(
        target_user_id=user_id,
        engagements=engagements,
        top_n=5
    )
    result = [
        {'topic_id': int(tid), 'score': float(score)}
        for tid, score in recs
    ]
    return jsonify({'recommendations': result})


# ---- ENDPOINT 3: Health check ----
@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'message': 'ML service running'})


if __name__ == '__main__':
    # Runs on http://127.0.0.1:5001
    app.run(debug=True, host='127.0.0.1', port=5001)

# Start the Flask server:
#   cd C:\Users\moses\Desktop\ml-service
#   python app.py

 #Test classification (open a new Command Prompt):
#curl -X POST http://localhost:5001/classify ^
 #    -H "Content-Type: application/json" ^
  #   -d "{"text": "how to use joins in SQL"}"
# Response:
# {"category": "Technology"}

# Test recommendations:
#curl http://localhost:5001/recommend/1
# Response:
# {"recommendations": [{"score": 2.12, "topic_id": 13}, ...]}

# Health check:
#curl http://localhost:5001/health
# {"message": "ML service running", "status": "ok"}