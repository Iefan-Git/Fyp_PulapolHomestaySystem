startNumber = int(input("Enter the starting number: "))
endNumber = int(input("Enter the ending number: "))

oddCount = 0
evenCount = 0

print("\n-------- EVALUATING NUMBERS --------")
for num in range(startNumber, endNumber + 1 ):
    if num % 2 == 0:
        print("Number ", num, " is even")
        evenCount = evenCount + 1
    else:
        print("Number ", num, " is odd")
        oddCount = oddCount + 1

print("\n---------- SUMMARY REPORT ----------")
print("Total count for even numbers : ", evenCount)
print("Total count for odd numbers  : ", oddCount)
print("------------------------------------\n")
