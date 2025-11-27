<!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        </head>  
        <style>

            body {
                margin: 0;
                padding : 0;
                align-items:center;
                justify-content: center;
                min-height : 100vh;
                background-color:#ced4da;
            }

           .divset {
                padding:20px;
                height:160px;
           }

            .container {
                background-color:white; 
                margin:40px; 
                position: absolute; 
                bottom: 10%;
                width:40%;
            }

            .form {
                width:400px;
            }

            .formFile {
                position:relative;
                width:100%;
                height:40px;
                cursor:pointer;
            }
         
            .formFile::after{
               content:attr(data-text);
               font-size:15px;
               position:absolute;
               top:0;
               left:0;
               background-color:#fff;
               padding: 10px 15px;
               display:block;
               width:calc(100% - 40px);
               pointer-events:none;
               z-index:20;
               height:40px;
               line-height:20px;
               color:#757575;
               border-radius: 5px 10px 10px 5px;
               font-weight:500;
               border:1px solid #BDBDBD;
            }
      

            .formFile::before{
               content:"Browse";
               position:absolute;
               top:0;
               right:0;
               display:inline-block;
               height:40px;
               line-height:40px;
               background:#E0E0E0;
               color:#757575;
               z-index:25;
               padding:0px 15px;
               font-size:15px;
               pointer-events:none;
               border-radius:0px 5px 5px 0px;
               font-weight:500;
               border:1px solid #BDBDBD;
            }
            
            .formFile input{
               opacity:0;
               position:absolute;
               top:0;
               right:0;
               bottom:0;
               left:0;
               z-index:99;
               height:40px;
               margin:0;
               padding:0;
               display:block;
               cursor:pointer;
               width:100%;
            }

            .btn_submit {
                background:#3e4af0 ;
                color: white;
            }

            .btn_submit:hover {
                background-color: #1B5E20;
                color: white;
            }
            
            .submit {
                padding:20px 0px;
                left:20%;
                position:fixed;
            }

        </style>
        <body >  
            <section >
                <div class="container">
                    <div class="divset">
                        <form action="importcsvfile.php" method="post" enctype="multipart/form-data">
                            <label for="formFile" class="form-label" style="font-weight:500;">Upload File</label>
                            <div class="formFile" data-text="Choose File">
                                <input class="form-control csvfile" name="csvfile" id="csvfile"  required type="file">
                            </div>
                            <div class="submit">
                                <button type="submit" class="btn btn-sm btn_submit">Submit</button>
                            <div>
                        </form>
                    </div>
                </div>
            </section>
        </body>
    </html>
    <script language="JavaScript" type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
    
    <script>

        $(".csvfile").on("change",function() {
            $(this).parent(".formFile").attr("data-text",$(this).val().replace(/.*(\/|\\)/,''));
        })

    </script>


