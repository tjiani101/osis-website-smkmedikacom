
import random

def generate_ids(n):
    ids = set()
    while len(ids) < n:
        length = random.randint(5, 10)
        id = str(random.randint(10**(length-1), 10**length - 1))
        ids.add(id)
    return list(ids)

ids = generate_ids(500)
for id in ids:
    print(id)
