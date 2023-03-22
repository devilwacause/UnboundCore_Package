<?php

namespace Tests\Unbound;

use Devilwacause\UnboundCore\Http\Controllers\ImageController;
use Devilwacause\UnboundCore\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageAPITest extends TestCase
{
    private $image;
    private $ImageController;
    private $current_folder;
    private $temp_folder;

    protected function setUp() : void {
        parent::setUp();
        $this->ImageController = new ImageController();
        $this->image = null;
        $this->current_folder = null;
        $this->temp_folder = null;
    }

    /** test */
    public function testBase64Upload(): void {
        $base64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD//gAtQ29udmVydGVkIGZyb20gIFdlYlAgdG8gSlBHIHVzaW5nIG
                    V6Z2lmLmNvbf/bAEMABQMEBAQDBQQEBAUFBQYHDAgHBwcHDwsLCQwRDxISEQ8RERMWHBcTFBoVEREYIRgaHR0fHx8TFyIkIh4kHB
                    4fHv/bAEMBBQUFBwYHDggIDh4UERQeHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHv/AAB
                    EIALUBAAMBIgACEQEDEQH/xAAdAAABBAMBAQAAAAAAAAAAAAAFAAMEBgECBwgJ/8QAPBAAAQMDAgMFBgQEBgMBAAAAAQACEQMEIQ
                    UxEkFRBiJhcZEHEyMygaEIFDNCUmKxwRUWQ9Hh8CRTkrL/xAAbAQACAwEBAQAAAAAAAAAAAAADBAECBQAGB//EACQRAAICAgIDAA
                    IDAQAAAAAAAAABAgMEESExEhNBFFEiMmGB/9oADAMBAAIRAxEAPwDyHR3UykDCg0fnCI24ECVpVLbFbHofpsMhSGUzAxvzWaNMQM
                    KbSpiBhaEKmxSywhGgYJC21evd6pfPvb+sa1Zwa0vduGtAa1oGwAAAAGAAERFAkYGCsfleeAfFFeIny0DVwAq2zYGVDrWzesKx1r
                    UZwPMKHVtJMR9kvZiPWw0Mj/Svmk4PicJ6lSdI7yIPtCHbLenbGRhKKiSDe3aGKFJ//Sp9C2e+OSetLUkjuyjmnacXFstnHVM10y
                    BOYJpWJI+WfqnzpuJ4FcLDSme6Es9QmhUsX6gbNgktwXDYnoOsbecppY8mgLtSfJS6+mx+z7qBcWRb/p5810W90iGlzRg+CAajpz
                    mSYx5IVmISrSm1Ld7f24TJpwcjKsde0mYBIUN9qATLZQXjsIrdgj3Tui1NIjl90X/LDoVobdsbFUljtF1agSWABNPphFKtsIJyod
                    VnJL2V+KCxmQymynqm+yYcEnNB0zBdBAU7Rb6hYXbq9xYUL6m6m+n7qsSGjiEB0gjIOR4ocQSd1lULiSKSRCggwkkkqnCW60W0rt
                    HEm2/UCM2jJIxgZQq2oniDtgjFoC3J6YWxRBpil0k+ifbskjoidtSDswFEtWjhBRewYOEfcrbojwZts+DNKhAADZ+q2Ntz4YRK2o
                    ggCApdO0DhkLRrqUlyZ87WivVLRrvBR61nDcAGOitD7Sn/AA8kxUsmkY3Vp4ifR0cjX0qNS0IPylJlrBHdKsr7B08vRJlg4xslZ4
                    WhmOVx2DNNsXPeDBhW7SNLLi2AUtI02XN7sq66NY0qNB9ev3adJhe4xsAFT0KPZLv39Kr2tu6Wh6O1lMzdV+6yP2AyC7ziR6rmVC
                    s+lUDmnzHVGu1upP1jWKt3kUx3KbZ+VoOAP6+ZKClhB6IErXF6RVT2dT7Mvpazo7XCn8WkAHgmfI/1Q/WNN4S7uy0+GyF+znVzYa
                    oKTzFOoAHDw/4MFdM1zT2ubxNaCHCZGx8U3VFWLbKe3T0cjvtP4ASAhlW0l0RC6BqenwCOGEAvLHhBPCrSxV8R35D+FXNsQYjZR6
                    9LhwAj1egQDIkKBdUpOEvZTrtB4Wv6BarDHqhtdkOI5I5cUuUdUOuKYk4ysq+rsdqlvkEV2R5hRHgohcNkwFFfSJ2CybYLY9Bkdj
                    OPi7zWw3i7xifAeKaCkOZ0C0LD5ILjoMmNQtgFtwlbASVGjtmhBPL7pQU6G42+6w5uNvuo8SRzUW2bawFi+s+jwNzUaA7igcWBym
                    VFCyd0lXRxYaNDYluOiI21MACQnmWdQEd0wFJo0OEAbL09eNJMx7LE+jNCmTECPBF7Gk4NBjmo9vR2xsillTPAMYlaVUdCU5bJ9k
                    wkCQjFtby0EjBUGypuJbIR6ypjgGIC0auBKwimzZzaFpUs2FsRCPUbYu2aMJw2bnDLBHknYcoWmisjTgeSfpaUDCsVHTpI7qJW2l
                    Agd3dDtRaLBekadlo4VA9qmqN0vSKekW9WLi5AdVjcU84+pA9FfbOyp2tB1zWhtKkwve48gBJXBu2mrVNb7QXV89xIc4hg/haMAf
                    RZeVLUQqALgDyhNPaAcD7p5+JwmKpgbSsjthobN7Wq63uGVWmC0+vgu59ib1uvdlKb3Omvan3Tidy0Dun6DH0XAH1DK6D7EtbFr2
                    lFlcVQyhds90SRIDye4TkbOgeRKew7VF6ZadM3HyLtrFgQwy3PWVVdQtXAlpGeRXUNdsoa6G8zjmPBUrV7VzQSWz0K2YpCabTKRc
                    0OEnu4Qi7pQ7aArRf04kwg1zSHEQRhByK9oPXY/pX7lkHKG3FOSYVgurfcESOqhG2k7BY19Ll0adVsUivVbfJJkYTDrbO6sFa0Bd
                    sNuiZdZ+ASE8T/AAZjkJdMAPtQM7/ZM1LeCMyj9W3AGwUWtRHIDCUsoUew8bvIDuo8Pimi2FPrtgbKDV3PmlJxSDxls1SJA5haPd
                    CbLkJsKgjbUNMfpV5Wr3lSnfMLfy1EU5bUBJ4iXftjHmhgPgkT9EkMsddZbbDaU+2wkA8AB6opQtmugubB5IhRs2kAESvpMaInjJ
                    Wy+Fep2LwRAwOSnW1KAARHVG6WnyR3ceakN02T8oBR440QXukRLKlluCEesaPEBjzKj21k5pAgIrZ0HMEHMjkulS0Sp7JlpSEBrW
                    +ZRCjbyACsabRhoJEkovbUCQDAKE5Sj0W8djFlp4dUaS2J6oxb6b/KOal6bbNLmmCY3zsrHZWLHtnhHhlKXXtdsNXTs5F7dNWZoH
                    Y0WFKoad1qJNMCILaTSC531JDQehK88srlx5eSt/4gO0Y132hXtC3qF1np7vylHkDwE8RHm4uM9AOiotuc7rOtu8uAsqXGOwhuJU
                    auYlPUzI3Wj6RdJEBAKR4eyC/fCl6RcPtb2nWY4tLXAgjkQZlautnROE0W8JHgr1vUth3Y3HR6rsLqnrfZyy1KmATWoNL4OeKIcC
                    ORBn1Va1y2HuyA2Tlafh91T/FOzt7o9RzfeWrhVptAiWuMO+8H6qya9YzTcQ3YwQtyizyZnSjpnLtQtTBHB90EuLQ8R7qveo2UtP
                    dQW4soOWp/1xl2DUnEqlW0LiMcky+zDMkBWK6tw1hI3GEMrtjJQLKILkLC2QGuKDBMDl0QyuwA9Eauy0TPWQgt7VaDAGJWVkJIeq
                    ZAuNyhty/hnZS7uo6YCF3TXEkkz0WDkyNSqHGyLc1twPVQKriTClV2EmfBR3s8Fl2MdhwNGea1W0EHAWqWYZGiSSSksejbWhJGI8
                    UWtLcAAkZjAQO0vmioJ57o9ZXlJ0AmMQMr6fGcTxDiEba1JLZG5RKhp7DHdkkJqwexwBBET1RzTxTe8AkEKzt10U8SDQ0sF4IZhT
                    7bSwDHuwcHmjNpasc8Y28UXtrBjjtyS9uVx2FhXsA2mnPJADQB0Ri2097WDuBFbTT4cIZIRa108kAcIHis+7L0t7G66QZptm7Ajz
                    W/bfVf8sdhdV1cgCpRt3CiSY+K7DI+pB8gVZLDTxxQII8AuOfi21ptlo+m9nKVUh9fiuazAN2g8LPplx8wFmO/2S5DTXqrbPK96C
                    +4dUMuLjkkyT4laUyQQp5oh8EjzWW2tMjDcqX2D9y8PFjFJ6cLnftMJ0WrRsISNIAAAQpAOS+DBdU2laFpd5qQGAcpSLBjC7ejky
                    /+wLWG6X2zt6bzwsuSbd/jxwG+jg1egdYtGE1GgbCT/deTez1WpbarSrUnFr2ODmkHYggz6heu23FLUdHtdRpQRc27aoI5y0H+60
                    sezWmClzIoeqWrWE93BVX1FgaTHQq6a22B6qnapSPEYHLqtyr+UdgJorl66GEbkoJevhpxlHri3cXZEhDbuydkwCCrWVScSISSfJ
                    W7trnE5jkEKuaLiY2jmrVVs2tb3h4qBcWrATHosnIxpPY/VdFFWuLZ4OcjqFBr0CZwrVXtwPEIdc0mZxBWRbjccj9V/wCmVqtauk
                    GQotW3IGfVHq9IE4Gyg3FME7LMux4x5HIWv6BalKDkfdMPEFE7inE4UKq0keIWbZHkbrnsjJLLhlYQQp2e2qVA9smUXtbmpLd1pa
                    aVJHfmecItaaKHFvxXhfS4RPGt7JFld1mgQ4geBKN2Gp1Ghskz4bhR7TQxA+NURex0AcYms9XcV9KoK6ZrDiQCXtA2JBCtOm6wCQ
                    SeSAWnZ8cA+M/fmj1l2fEN+M9LXKvWmGrUvhZtN1K3c0NLo8ZVi02vSfAJBHLKq9h2fpS34j+XNWTTNFDY+PU9ViZbgt6H6trssN
                    oykWTTweYXiH8Q3aVnaD2p6tc0Kgfb0av5aiWmWltMBsjwJBMr2F2xvv8ALPYjWNZNYj8jaVKjXERL4hg8O8QF89ry4dWuH1Xkuc
                    4ySfNK48d+Uv8AhN/MlD4PU6wB6J8VzGDIQtzzyMLdlUiJyjCk6mwp79mJTZrB2BjxUQPDluCrAvW0Ply14/BN8S194BuuLeDZKo
                    vNOqHjBBlesPYtXo6t7PqLHvDqlnVfQMGRBhzfpDo8h4LyQKrTsu8fhm1p1Manpb3E+9pNqMb4tJBPo4eiYhtrS7BNeMls6jrGk2
                    3elo9AqtqWkW0mG8ugRjXNVfBIn0VJ1fWK0/qOGNgYW1iKzXLK2pfDF7pVuARwx4wg93pdEg5Kg6jrT3NLW16sjxQa61mrwEe8ft
                    /EtFSa7FtbYUudKon9x2Qy50mjBhxB8kIutXq5+LU+jkMudZrQfi1PVJZORBbGa62+gvdaO2f1DHSENu9FpwTxunrCFXOsXXKvUH
                    1Qy51i6M/+TX+jlj23QHqqZhOvo7ZPxHD6KBX0dvF+q70Qyrq90D+vVPmVCq6vd8X67/VZORfWP11TCFxo/C4w8unriFDq6OQYJj
                    zUCtqt255is71TD9RvD/rO9VmWW1b6HIVWIJHR3ii+mH0jxEHic2XCJwDyGdvAdE1/gNQ/6rfQqAdRu/8A3FYOoXZP67x5GEPzq/
                    QZRs+s9E2lejA8UYsrq3aBgyVUbR+BhFbIzw4iF9HjJfDxrRb7W9ozgFF7G+pcUZ5KpWUbwjtkWt6ZUy5O3otlnfM+XMAIzaX7Ya
                    qja1mcAacEH1RixqskSRhKW1toNCzRdNPuXnhPU9VYLCs8lufuqhp1ZkNyN1YrG5Z3dvVYeVU3wjRpmtcnPPxd68+y9nFDSaTiHa
                    ldAPgxLGCSD4cRYF45qAgru34vNfdedurPRqbz7rTrNvE2ce8qEuJ/+eBcNfB5wUGEfGCRE5v2NoYJnmsgrfgPLKQbByrku3jWhA
                    mMyPqnGOIPWU5Rptd8yc9yMEBSLuSRoMym3yTkKSKR5rLqIxgLivlEjMJkLqf4fr1tDthasmDVmifHiBgeoC5r7joArF2BuHWPaG
                    0rteWljw4QYkggj+iLS9SA3yj4np7X6BcXOAHjiVQ9botD+EUmiBk9V0bWnMqNL6ZlrgHNPUESCqJrg+Kc8ivQYL32Bse1somp0a
                    ADppCeZQG6o0SI92PVWrVaQIdgKvVwBONk/ZFNAY8MA3drT4y5ojG0oVeW7eRIR+7AEnYFBrthglZGTXEfoloDXdKCfLqhtaiZJJ
                    Re4a6DzMIdcNMHwKw749mnTID3NGoO8CMKBVa8HvDHgi1248JQ+ue7ssa+PJoVyIb55YTUYKeecjCbJAJnokJLTGUNz4pSUiIKwq
                    ljvtpsJRWzeGcMqt0bqGjvlSqV3IALzHkvp8HH9nipQLbQu2NMA+am29+Ng47qoULkBwh3nKn210AesnqmIyguwDi98Fxtrw4IdK
                    N2F7IBlUqzuZAiMo5p9b5cjdDunFx4CQTXZfNNvJAIIVm0ms5zgBHJUfSntgZ3Viub9ul9ntR1F7uFtta1Ks9CGkj7gLGuh9Hap/
                    Dyj7W9adrntF1vUeLibUuntYf5GQ1v2aFVWvkiRut71zqty+oSXFxkk7mTJKZAys6XehqS/iPNeADjda8beLCwRhNl3CduarsrXW
                    57CFsQRnc4UtjYgwhdCoRkK8ezvsL2h7Z1uOxoCjp7XRVvK0im082jEudjYTGJhWXIvZXJfCtvgQBmVlg4jGy9B0PYZojLIMdrF1
                    UuyMvNNoaPAAQQPquU9teyF92b1GpbV2AhuzmmWuHUGNv+7yiqttbQpOXh/YrDKUiSFN0pvu76i4fxDmofvHN2xyTlCrw1GmSCDI
                    PRdHiSKTi5Lg9L0NQ972dsaznS40WgnyEf2+6q2sXB4yS6SU72Tuxddj7chxdwS0k/U/3QbW6hlx6bLdxpJclFuUUgVqdwSXQ4GN
                    0Cuagytr+6LSRPmhFzdwT3inHfH6TGtm9y4Fo8EMvPlKcrXXdGVBuK4IOVnZFsXsdqgyHXdlD7k/Mptd4JlD7k4KxrutmjWmQLoD
                    hOFArtHAceIU+5nhOEOuHjhPSICxrnyP1L6Q3hNObunHk9EyZk4WfPscRqd1hJJULHUKN5U4R3gpVG7qEDIVeo1iWiCVOtahIEr2
                    kchvpnmp0osNpcue8hxEATKLWTy50EyIwq7p5PGT4I7ZODU7TKUuxaUVHosFg4gDOFYNOeICqtvcQMYRjTrkHhziE4obF5S0X3Sa
                    rAG7Jr2rap+S9mOrSZNYMoDP8AE4T9gUO0y4ILSDhVb8Rmqmj2Q0yzpOM3F0XkciGNMz9XBK5UVGtsLjfzsSOJVKzDUc44BOE2az
                    ccOUOdcOcMpn8wWmRK85O57N38WKQYbVlNvfxEY2S0ejc6ne0bKzoVLi4rO4WU6beJzieQ/vsAJJIAXoj2VezfTuypo6trIo3usA
                    B1MCHUbZx6cnPEwXHYjHUmorlc9IHZOFC2AfZV7HKl3Toa12zZVtrV5DqNhMPqDrU5tBxDdyDkjY91/OWWn21KytabaFvRaGUqNI
                    BrGAbANGAEE1TWIDhxyR6DyVcvNaLqpzstynB0tNGPflyk9ovY1gE/McIP230y37SaK6kYNxSBdRJOCcSw+BA35EA5iFUxrMk94I
                    np2s7Di+6M8aMehaVjsjqR571xj7K+fScwtgxB5EYPoZH0Q8XWCCPuur+2Xs626YNbsqYPG7hrhogNdHdcfAgAE9QOuOLvqlhIcC
                    DzCx8mPrlsdxaXZHg717LLj3/Zu4Y1xIYWug8pEf2KWv7OM8iq37ENR4xd2nHIfRLgPJw/3Vh194h2OcLSpsTghZVODaZSNXPC4w
                    eqr1zXdxHmjermCc9VVbyrBdhAut0xqqtMzWujw5IwotS6AnicotevjmVCq15JnJWTdktN7Y/XSidUu2fxKNXuRBgjPioNSuCdlH
                    q15BH2SdmVv6NRqJFatxgiRlQ65DhuMJt1WMpp1SepSNl0WxqENIyRiJTbm4PmkXDx9UnOwlm9hENHdYWTusKoQuFsTA8ETtNh4l
                    DKToiFOoVQAAvUUvRiWLgOWrgzPXCKWteAq/b1sCd1Mp1yAJkBalNkUITiWGjc4xlFdOuWgDOJ9FUqN2AcOhEbK7yIP3wU6siIvK
                    pvo6HpV4MZVE/EPcGrV0q3a8kU6L3lvQuIB/8AyjWkXcvHeVN9sF2LnXGNmeCixv2J/ulM26LqYxh1SVqOaOcRyT+j6VqOt6pQ03
                    S7V9xc1nQ1jeXieQA5k4AU/RNDvtd1Knp+nUg6s8SS4w1jREuceQE5P9SQF37sLoGm9jdKLbdwrX9ds3N0R3nyPlGJDQYwD5kkrC
                    px5XS46Nm7JhXH/SV7M+xul9g7Iva+jd6vVYW3F2MhoO7KfRvUkAk7wAAD19q5FTLj5zhANR1YF5c55E8pQG51QOeTxkGeuy9Li1
                    V1LUUefunK17kHNT1c/E76A3Gp/wA/2Q69vQ8zy/qULrXeSSJKcdsYi/rbDo1PPzolpup7fE5qkuumk7EKXaXnCRB+iVtvTCwqOn
                    0rxl1a1LasG1aNVhZVYRhzTuFwXt5o79G7QVrXLqZHHRfEBzDtjkRBBGcgrpNhqoaAOIz1lLtTRttd0wUqgaa9Il1FxMAE7g+Bgf
                    UBZ+RD2oex263tFO9jtx7nXz3y3ipPaMfyroWt1mEOk7yub9j6LrDtIMEFpJbOIkEEff7K16tfcVQiJGefRRW/CKiWsSsnwAtYqH
                    iKqmoPy7CN6lXdUe6Cq3evIqGcwl77OwtVWiHXceFD7ioQTzUu5fjbdDrg7rFvnzs0KoDb6spg1MrV7v8AspvdZ057Y0oIlVKT/w
                    Aq25BHAXcIzJ+qjEylxnh4ZMTtK1KGX0KT1WTstFsuJMHdYSSXElqpOyPBSqVTIz90OY/KdY/H/K3YWaMqUdhanXgACMJ5tzUj/Z
                    BRWIzKybh4/cjLJ0C9SYaFy6diplrexEmCqx+c6uP0TlC873zmFb8tfst+OdB03UI4TxQdpVe11lxrXaF9GhBOGl5MNYABJJ6Y5Z
                    lRLPUwwgEmBsQidheU6AeaZE1HFzjzlc7o2LTfBEYOD2Xbs9b6foFmaFkO84TVrOgvqEdTyA5AbJ6vrD3GTUKprtWdjvDbqmDqbn
                    fuATdN0ILxXQtOryfkyyXmocROSZ5kobWujyJ36oPUvZk8RJ80w+7jmT9U1+VFdMF6QpcXJj5ioFW5M/M7dQq11PX1USpcZ3KDZl
                    p/SVQFHXJIyT6pUbxwfIccINUuNsn1WouQDufVKyyv9DwoLdaaiZAcfrKKUNQhgIfKobLuBufVP09RcGwCY80P8xL6EVBaLyqwXb
                    btjuFwMGOcqPqF/wBwnjQJ1+SO84n6qLc3pcSZx5qk8va7L14/i2yVd3ncIbg8yg9erxE5lN3N0cmcqBVuCCTJKTnkNvsYhXo3r1
                    ZJE5UOvUnAMlJ78zCbSVk/J6GYrQy/ktU69uP+U0RlKyWgyEkkkoRIgnHUqgotrGm73biQ18YJG4+ifvnsLm0ab2VKdJvCyo2lwF
                    w3z1OYk9FHdVeaQpF7ixpJa0nAJiY9FJxokspLjgyHHqs8TuqSS1BBmHOMbppzj1KSSpMldjTnuBC3FRwbIMJJIbCMmW1V+JKI0a
                    70kkzV0Bmh5td5S988dEkk1EXZn3rnA5hNveY3KSSIVGar3fdR6j3deaSSDYw0Ehp7nYymuN3FukklpBEZ4ndSlxvGzikkh6LITq
                    r4ySmXVHn9xSSQmwy6I1ZziTlMEnqkkhTZbQgTzVk9nmk2ut9qbPTrwONGq8B3CYJBSSUV/wBiTb2iaRa6L2ovdNtAfc0Xw2d1VX
                    fMUklSz+wRGqSSSoyRSkkkqnGU9YsFS7ptdsXJJKTj/9k=';



        $request = Request::create('/api/images/upload', 'POST', [
            'file_base64' => $base64,
            'filename' => 'Star Trek Space',
            'folder_id' => null,
            'folder_name' => 'Unbound Image Test',
            'width' => '256',
            'height' => '181',
            'title' => 'Star Trek Space',
        ], [
            'Content-Type' => 'application/json',
        ]);

        echo "Test Image Upload" .PHP_EOL;
        $response = $this->ImageController->create($request);
        echo "Assert Response === 200" .PHP_EOL;
        $this->assertEquals(200, $response, "Create call ran unsuccessfully");
        $this->image = Image::where('title', 'Star Trek Space')->where('width', '256')->first();
        echo "Assert Record Created In Database" .PHP_EOL;
        $this->assertNotEmpty($this->image, "Image was not created");
        $this->assertInstanceOf(\Devilwacause\UnboundCore\Models\Image::class, $this->image, "Image was not returned");
        echo "Assert Image Saved to Disk" .PHP_EOL;
        $this->assertTrue(Storage::exists($this->image->file_path), "Directory was not created");

        $this->updateImage();
        $this->changeImage();
        $this->moveImage();
        $this->moveImageBack();
        $this->copyImage();
        $this->removeCopy();
        $this->cleanUpTest();
    }

    /** test */
    public function updateImage() {
        echo "Test Image Update" .PHP_EOL;
        $request = Request::create('/api/images/update', 'POST', [
            'file_id' => $this->image->id,
            'title' => "Star Trek Space Update"
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->update($request);
        echo "Assert Response === 200" . PHP_EOL;
        $this->assertEquals(200, $response, "Failed to update the image data 1");

        $request = Request::create('/api/images/update', 'POST', [
            'file_id' => $this->image->id,
            'title' => "Star Trek Space"
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->update($request);
        $this->assertEquals(200, $response, "Failed to update the image data 2");

    }

    public function changeImage() {
        echo "Test Image Changing" .PHP_EOL;
        $base64 = 'data:image/jpeg;base64,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';
        $request = Request::create('/api/images/change', 'POST', [
            'file_id' => $this->image->id,
            'file_base64' => $base64,
            'filename' => 'Enterprise',
            'title' => "enterprise",
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->change($request);
        echo "Assert Response === 200" .PHP_EOL;
        $this->assertEquals(200, $response, "Failed to change image during call");

        $this->image = Image::where('id', $this->image->id)->first();
        $this->assertEquals("enterprise", $this->image->title, "Image was not changed");

        $request = Request::create('/api/images/update', 'POST', [
            'file_id' => $this->image->id,
            'title' => "Star Trek Space"
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->update($request);
        $this->assertEquals(200, $response, "Failed to revert the image title");
        $this->image = Image::where('id', $this->image->id)->first();
    }

    public function moveImage() {
        echo "Test Image Move" .PHP_EOL;
        echo "Test with non-existant folder";
        $this->current_folder = $this->image->folder_id;
        $request = Request::create('/api/images/move', 'POST', [
            'file_id' => $this->image->id,
            'folder_id' => 99999
        ],[
            'Content-Type' => 'application/json',
        ]);
        $this->expectException(\Devilwacause\UnboundCore\Exceptions\FolderExceptions\FolderNotFoundException::class);
        $response = $this->ImageController->move($request);
        $this->assertEquals(404, $response, "Image move failed on the bad test");
        echo "Create Folder For Move";
        $data = [];
        $data['parent_id'] = null;
        $data['folder_name'] = "Unbound Image Test 2";
        $folder = $this->ImageController->createFolder($data);
        $this->assertInstanceOf(\Devilwacause\UnboundCore\Models\Folder::class, $folder, "Folder for test was not created");
        $request = Request::create('/api/images/move', 'POST', [
            'file_id' => $this->image->id,
            'folder_id' => $folder->id,
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->move($request);
        $this->assertEquals(200, $request, "Image move failed on the good test");
        $this->image = Image::where('id', $this->image->id)->first();
    }

    public function moveImageBack() {
        echo "Return file back to original folder";
        $tempFolder = Folder::where('id', $this->image->folder_id)->first();
        $this->assertInstanceOf(\Devilwacause\UnboundCore\Models\Folder::class, $tempFolder, "Failed to grab the temp folder");
        $this->tempFolder = $tempFolder;
        $request = Request::create('/api/images/move', 'POST', [
            'file_id' => $this->image->id,
            'folder_id' => $this->current_folder
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->move($request);
        $this->assertEquals(200, $request, "Image move failed to move back to original folder");
    }

    public function copyImage() {
        echo "Try to copy file to non-existant folder";
        $request = Request::create('/api/images/copy', 'POST', [
            'file_id' => $this->image->id,
            'folder_id' => 999999
        ],[
            'Content-Type' => 'application/json',
        ]);
        $this->expectException(\Devilwacause\UnboundCore\Exceptions\FolderExceptions\FolderNotFoundException::class);
        $response = $this->ImageController->copy($request);
        $this->assertEquals(404, $response, 'Something wrong - found a non-existant folder');
        echo "Try to copy file to the secondary folder";
        $request = Request::create('/api/images/copy', 'POST', [
            'file_id' => $this->image->id,
            'folder_id' => $this->temp_folder->id
        ],[
            'Content-Type' => 'application/json',
        ]);
        $response = $this->ImageController->copy($request);
        $this->assertEquals(200, $response);
    }

    public function removeCopy() {
        $copyImage = Image::latest();
        $this->assertInstanceOf(\Devilwacause\UnboundCore\Models\Image::class, $copyImage, 'Failed to get copy image');
        $this->assertTrue(Storage::delete($copyImage->file_path), "Failed to delete copy image");
        //Delete the temp folder
        $folder = $this->ImageController->getFolderPath($this->temp_folder);
        $this->assertTrue(Storage::deleteDirectory($folder));
        $copyImage->delete();
    }

    /** test */
    public function cleanUpTest() : void {
        $this->assertTrue(true);
        echo "Clean up testing" .PHP_EOL;
        $this->assertTrue(Storage::delete($this->image->file_path), 'Failed to delete original file');
        $this->image->delete();
        $this->assertTrue(Storage::deleteDirectory('public/Unbound Image Test'), 'Failed to delete original directory');
    }
}
