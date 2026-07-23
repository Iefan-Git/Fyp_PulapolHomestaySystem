customerName = input("Enter the customer name: ")
ticketPurchased = int(input("Tickets purchased: "))

ticketPrice = 35.00
totalPayment = ticketPurchased * ticketPrice

# The customer will get a discount if they buy more than 5 tickets
if ticketPurchased > 5:
    discount = totalPayment - 30.00
    print("You got a RM 30 discount")
else:
    discount = totalPayment 

# All the output 
print(f"\n-------------------RECEIPT DETAILS--------------------")
print(f"Customer name                        : {customerName}")
print(f"Ticket purchased                     : {ticketPurchased}")
print(f"Total Payment                        : {totalPayment}")
print(f"Price after discount (if applicable) : {discount} ")
print(f"------------------------------------------------------\n")