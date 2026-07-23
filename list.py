fruits = ["Apple", "Banana", "Cherry", "Orange", "Grape"]

# List for the output for fruits
print("----- FRUIT LIST -----")
for fruit in fruits:
    print(f"{fruit}")

# User need to add 2 more output
print("\n----- ADD NEW FRUITS -----")
fruit1 = input("Enter the first additional fruit: ")
fruit2 = input("Enter the second additional fruit: ")

fruits.append(fruit1)
fruits.append(fruit2)

# The 2 new fruit will add and appear on here
print("\n----- UPDATED FRUIT LIST -----")
for fruit in fruits:
    print(f"{fruit}")