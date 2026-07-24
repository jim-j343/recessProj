#test_classifier.py
import joblib
from pathlib import Path

# Load the saved model from next to this script
classifier = joblib.load(Path(__file__).resolve().parent / 'topic_classifier.pkl')

# Test with new topics the model has never seen — all genuinely belong
# to one of the 12 trained categories, spanning a good spread of them
test_topics = [
    "how do you handle merge conflicts in git",
    "what is the difference between a primary key and a foreign key",
    "explain the waterfall model of software development",
    "how does the newton raphson method actually work",
    "what makes a good user story for a sprint",
    "difference between tcp and udp protocols",
    "when should you split a monolith into microservices",
    "how does a scrum team estimate story points during planning",
    "what is the difference between a stack and a heap in memory",
    "how do you mock a database when writing unit tests",
    "how do you validate a form before submitting it to the server",
    "what does an inner join return when there is no matching row",
    "why does gauss seidel converge faster than jacobi",
]

for topic in test_topics:
    predicted_category = classifier.predict([topic])[0]
    print(f"Topic: '{topic}'")
    print(f"Predicted: {predicted_category}")
    print()


# Run it:
#python test_classifier.py