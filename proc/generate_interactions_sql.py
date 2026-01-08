import json
import os

def generate_sql():
    # Read interactions
    interaction_file = 'data/interactions.json'
    if not os.path.exists(interaction_file):
        print(f"Error: {interaction_file} not found")
        return

    with open(interaction_file, 'r') as f:
        interactions = json.load(f)

    # Extract unique users
    users = {}
    for i in interactions:
        uid = i['user_id']
        if uid not in users:
            users[uid] = {
                'id': uid,
                'name': f"User {uid}",
                'email': f"{uid}@example.com",
                'password': 'hashed_dummy_password'
            }

    # Generate SQL
    with open('interactions_seed.sql', 'w') as sql:
        sql.write("USE sneakeazy;\n")
        
        # 1. Users
        sql.write("-- Seeding Users\n")
        user_values = []
        for u in users.values():
            # Escape strings just in case
            uid = u['id'].replace("'", "''")
            name = u['name'].replace("'", "''")
            email = u['email'].replace("'", "''")
            pw = u['password']
            user_values.append(f"('{uid}', '{name}', '{email}', '{pw}')")
        
        # Batch insert users
        if user_values:
            sql.write(f"INSERT IGNORE INTO users (id, name, email, password) VALUES\n")
            sql.write(",\n".join(user_values))
            sql.write(";\n\n")

        # 2. Interactions
        sql.write("-- Seeding Interactions\n")
        interaction_values = []
        for i in interactions:
            uid = i['user_id'].replace("'", "''")
            pid = i['product_id'].replace("'", "''")
            rating = i.get('rating', 'NULL')
            ts = i.get('timestamp', 0)
            interaction_values.append(f"('{uid}', '{pid}', {rating}, {ts})")
        
        # Batch insert interactions (chunked to avoid max packet size issues if huge)
        chunk_size = 1000
        for i in range(0, len(interaction_values), chunk_size):
            chunk = interaction_values[i:i + chunk_size]
            sql.write(f"INSERT IGNORE INTO interactions (user_id, product_id, rating, timestamp) VALUES\n")
            sql.write(",\n".join(chunk))
            sql.write(";\n")

    print(f"Generated interactions_seed.sql with {len(users)} users and {len(interactions)} interactions.")

if __name__ == "__main__":
    generate_sql()
