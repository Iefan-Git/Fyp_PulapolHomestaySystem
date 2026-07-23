number1 = float(input("Enter the first number: "))
number2 = float(input("Enter the second number: "))

if number1 > number2:
    print("\nThe first number is larger than second number")
elif number2 > number1:
    print("\nThe second number is larger than first number")
else:
    print("\nThe first number and second number is equal")
    
if number1 == number2 or number2 == number1:
    print("The both number is equal")
else:
   print("The both number is not equal") 